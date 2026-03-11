<?php

namespace Invezgo\Service;

/**
 * Search Service
 */
class SearchService extends BaseService
{
    /**
     * Search stock or user
     *
     * @param string $query Search query
     * @return array
     */
    public function search(string $query): array
    {
        return $this->client->get('/search', ['query' => $query]);
    }

    /**
     * Search stock
     *
     * @param string $query Search query
     * @param string|null $cursor Cursor for pagination
     * @return array
     */
    public function searchStock(string $query, ?string $cursor = null): array
    {
        $params = ['query' => $query];
        if ($cursor !== null) {
            $params['cursor'] = $cursor;
        }
        return $this->client->get('/search/stock', $params);
    }

    /**
     * Search user
     *
     * @param string $query Search query
     * @param string|null $cursor Cursor for pagination
     * @return array
     */
    public function searchUser(string $query, ?string $cursor = null): array
    {
        $params = ['query' => $query];
        if ($cursor !== null) {
            $params['cursor'] = $cursor;
        }
        return $this->client->get('/search/user', $params);
    }
}
