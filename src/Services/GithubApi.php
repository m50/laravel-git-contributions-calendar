<?php

namespace m50\GitCalendar\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use m50\GitCalendar\GitData;
use m50\GitCalendar\Contracts\GitApi;

class GithubApi implements GitApi
{
    private $key;
    private $uri;
    /**
     * The Guzzle Client.
     *
     * @var GuzzleHttp\Client
     */
    protected $guzzle;
    /**
     * The earliest date to be collecting from.
     *
     * @var Carbon\Carbon
     */
    protected $after;
    /**
     * A collection of events from response.
     *
     * @var Collection
     */
    protected $events;
    /**
     * An array of response headers from the last request.
     *
     * @var array
     */
    protected $responseHeaders;
    /**
     * Collection of all the repos of the Users.
     *
     * @var Collection
     */
    protected $repos;

    /**
     * The user array received. 
     *
     * @var array
     */
    protected $user;

    /**
     * The username of the user to get.
     *
     * @var string
     */
    protected $username;

    /**
     * The constructor.
     *
     * @param string $username
     * @param string $key
     * @param string $uri
     * @return void
     */
    public function __construct(string $username, string $key, string $uri)
    {
        $this->key = $key;
        $this->uri = $uri;
        $this->username = $username;
    }

    public function init ()
    {
        $this->guzzle = new Client([
            'base_uri' => $this->uri,
            'headers' => [
                'Authorization' => "token {$this->key}",
                'Accept' => 'application/vnd.github.v3+json'
            ]
        ]);
        $this->after = Carbon::parse(Carbon::now()->subMonths(12)->toDateString());
        $this->queryUser($this->username);
    }

    /**
     * Query the user from the username.
     *
     * @param string $username
     * @return self
     */
    public function queryUser(string $username): self
    {
        $response = $this->guzzle->get("/users/{$username}");
        $this->user = collect(json_decode($response->getBody()->getContents(), true));
        return $this;
    }

    /**
     * Query all of the repositories for for the user.
     *
     * @return self
     */
    public function queryRepos(): self
    {
        if (!isset($this->guzzle)) {
            $this->init();
        }
        $response = $this->guzzle->get("/users/{$this->user['login']}/repos");
        $this->responseHeaders = $response->getHeaders();
        $this->repos = collect(json_decode($response->getBody()->getContents(), true))
            ->map(function ($repo) {
                return $repo['name'];
            });
        return $this;
    }

    /**
     * Query all the commits in a repository.
     *
     * @param  string $repo
     * @param  int    $page
     * @return self
     */
    public function queryCommits(string $repo, int $page = 1): self
    {
        $options = collect([
            'page' => $page,
            'per_page' => 60,
            'since' => $this->after->format('Y-m-d\T00:00:00\Z'),
        ]);
        $options = $options->map(function ($val, $key) {
            return "$key=$val";
        })->implode('&');
        $response = $this->guzzle->get("/repos/{$this->user['login']}/{$repo}/commits?{$options}");
        $this->responseHeaders = $response->getHeaders();
        $this->events = collect(json_decode($response->getBody()->getContents(), true))
            ->filter(function ($e) {
                return $e['commit']['author']['email'] = $this->user['email'];
            })->map(function ($e) {
                $e['commit']['author']['date'] = Carbon::parse(Carbon::parse($e['commit']['author']['date'])->toDateString());
                unset($e['commit']['committer']);
                unset($e['committer']);
                unset($e['author']);
                unset($e['parents']);
                return $e;
            });
        return $this;
    }

    /**
     * Get the commit counts by day.
     *
     * @return GitData
     */
    public function getEventCountsByDay(): GitData
    {
        $data = collect();
        foreach($this->queryRepos()->repos as $repo) {
            $this->queryCommits($repo, 1);
            $totalPages = $this->getTotalPages();
            $data = $data->merge($this->events->map(function ($e) {
                return $e['commit']['author']['date'];
            }));
            for ($i = 2; $i <= $totalPages; $i++) {
                $this->queryCommits($repo, $i);
                $response = $this->events->map(function ($e) {
                    return $e['commit']['author']['date'];
                });
                $data = $data->merge($response);
            }
        }
        $data = $data->groupBy(function ($e) {
            return $e->dayOfWeek + 1;
        })->map(function ($eg) {
            return $eg->groupBy(function ($eg1) {
                return $eg1->diffInDays($this->after);
            })->map(function ($e) {
                return [
                    'date' => $e[0],
                    'count' => $e->count(),
                ];
            });
        });

        return new GitData($data, $this->after, Carbon::parse(now()->toDateString()));
    }

    /**
     * Getter for the data.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($name == 'events') {
            return $this->events ?? collect();
        } elseif ($name == 'responseHeaders') {
            return $this->responseHeaders ?? collect();
        } elseif ($name == 'headers') {
            return $this->responseHeaders ?? collect();
        }
        return $this->$name;
    }

    /**
     * Get total number of pages out of the Link header.
     *
     * @return int
     */
    private function getTotalPages(): int
    {
        if (!isset($this->responseHeaders['Link'])) {
            return 1;
        }
        $lastPageLink = collect(explode(',', $this->responseHeaders['Link'][0]))->map(function ($m) {
            return collect(explode('; rel=', $m))->map(function ($k) {
                return trim($k, ' <>"');
            });
        })->filter(function ($d) {
            return $d[1] == 'last';
        })->map(function ($k) {
            return $k[0];
        })->implode('');
        return (int) explode('page=', $lastPageLink)[1];
    }
}
