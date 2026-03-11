<?php

namespace Invezgo\Service;

/**
 * Alerts Service - Live Alert
 */
class AlertsService extends BaseService
{
    /**
     * Get list of alerts
     *
     * @return array
     */
    public function list(): array
    {
        return $this->client->get('/alerts');
    }

    /**
     * Create new alert
     *
     * @param array $data Alert data with formula
     * @return array
     */
    public function create(array $data): array
    {
        return $this->client->post('/alerts', $data);
    }

    /**
     * Update alert
     *
     * @param string $id Alert ID
     * @param array $data Alert data
     * @return array
     */
    public function update(string $id, array $data): array
    {
        return $this->client->put("/alerts/{$id}", $data);
    }

    /**
     * Delete alert
     *
     * @param string $id Alert ID
     * @return array
     */
    public function delete(string $id): array
    {
        return $this->client->delete("/alerts/{$id}");
    }

    /**
     * Test alert formula
     *
     * @param array $data Test data with formula
     * @return array
     */
    public function test(array $data): array
    {
        return $this->client->post('/alerts/test', $data);
    }
}
