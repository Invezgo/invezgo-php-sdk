<?php

namespace Invezgo\Service;

/**
 * Screener Service
 */
class ScreenerService extends BaseService
{
    /**
     * Get list of screener presets
     *
     * @return array
     */
    public function list(): array
    {
        return $this->client->get('/screener');
    }

    /**
     * Save screener preset
     *
     * @param array $data Screener data
     * @return array
     */
    public function save(array $data): array
    {
        return $this->client->post('/screener', $data);
    }

    /**
     * Update screener preset
     *
     * @param string $id Screener ID
     * @param array $data Screener data
     * @return array
     */
    public function update(string $id, array $data): array
    {
        return $this->client->put("/screener/{$id}", $data);
    }

    /**
     * Delete screener preset
     *
     * @param string $id Screener ID
     * @return array
     */
    public function delete(string $id): array
    {
        return $this->client->delete("/screener/{$id}");
    }

    /**
     * Run screener
     *
     * @param array $data Screen data with formula
     * @return array
     */
    public function screen(array $data): array
    {
        return $this->client->post('/screener/screen', $data);
    }
}
