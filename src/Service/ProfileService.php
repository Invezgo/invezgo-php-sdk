<?php

namespace Invezgo\Service;

/**
 * Profile Service
 */
class ProfileService extends BaseService
{
    /**
     * Get user profile details
     *
     * @param string $username Username
     * @return array
     */
    public function userDetails(string $username): array
    {
        return $this->client->get("/profile/detail/{$username}");
    }

    /**
     * Get user posts
     *
     * @param string $username Username
     * @param string|null $page Page number
     * @param string|null $limit Limit
     * @return array
     */
    public function userPosts(string $username, ?string $page = null, ?string $limit = null): array
    {
        $params = [];
        if ($page !== null) {
            $params['page'] = $page;
        }
        if ($limit !== null) {
            $params['limit'] = $limit;
        }
        return $this->client->get("/profile/posts/{$username}", $params);
    }

    /**
     * Get user posts by category
     *
     * @param string $username Username
     * @param string $category Category
     * @param string|null $page Page number
     * @param string|null $limit Limit
     * @return array
     */
    public function categoryPosts(string $username, string $category, ?string $page = null, ?string $limit = null): array
    {
        $params = [];
        if ($page !== null) {
            $params['page'] = $page;
        }
        if ($limit !== null) {
            $params['limit'] = $limit;
        }
        return $this->client->get("/profile/posts/{$username}/{$category}", $params);
    }

    /**
     * Get user watchlist
     *
     * @param string $username Username
     * @return array
     */
    public function listWatchlist(string $username): array
    {
        return $this->client->get("/profile/watchlist/{$username}");
    }

    /**
     * Get user followers
     *
     * @param string $username Username
     * @return array
     */
    public function followUser(string $username): array
    {
        return $this->client->get("/profile/follow/{$username}");
    }

    /**
     * Get user following
     *
     * @param string $username Username
     * @return array
     */
    public function getFollowingUser(string $username): array
    {
        return $this->client->get("/profile/following/{$username}");
    }

    /**
     * Get user memberships
     *
     * @param string $username Username
     * @return array
     */
    public function getMemberships(string $username): array
    {
        return $this->client->get("/profile/membership/{$username}");
    }
}
