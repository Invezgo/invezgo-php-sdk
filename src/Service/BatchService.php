<?php

namespace Invezgo\Service;

/**
 * Batch Service - Batch Stock Data API
 */
class BatchService extends BaseService
{
    /**
     * Format stock/index codes array or string into pipe-separated string.
     *
     * @param string|array $code Code string or array of codes
     * @return string
     */
    private function formatCodes($code): string
    {
        if (is_array($code)) {
            return implode('|', $code);
        }
        return (string) $code;
    }

    /**
     * Get batch order book data for multiple stock codes in 1 request.
     *
     * @param string|array $code Stock code(s) separated by pipe/comma or array (e.g. 'BBCA|GOTO|HUMI' or ['BBCA', 'GOTO', 'HUMI'])
     * @param string $market Market type: RG (Reguler), NG (Negotiated), TN (Tunai). Default: RG
     * @param string|null $date Date in YYYY-MM-DD format
     * @param string|null $time Time in HH:MM format
     * @return array
     */
    public function getOrderBook($code, string $market = 'RG', ?string $date = null, ?string $time = null): array
    {
        $codeStr = $this->formatCodes($code);
        $params = [
            'market' => $market,
        ];

        if ($date !== null) {
            $params['date'] = $date;
        }
        if ($time !== null) {
            $params['time'] = $time;
        }

        return $this->client->get("/batch/order-book/{$codeStr}", $params);
    }

    /**
     * Alias for getOrderBook.
     *
     * @param string|array $code Stock code(s)
     * @param string $market Market type
     * @param string|null $date Date
     * @param string|null $time Time
     * @return array
     */
    public function orderBook($code, string $market = 'RG', ?string $date = null, ?string $time = null): array
    {
        return $this->getOrderBook($code, $market, $date, $time);
    }

    /**
     * Get batch intraday data for multiple stock codes in 1 request.
     *
     * @param string|array $code Stock code(s) separated by pipe/comma or array (e.g. 'BBCA|GOTO|HUMI' or ['BBCA', 'GOTO', 'HUMI'])
     * @param string $market Market type: RG (Reguler), NG (Negotiated), TN (Tunai). Default: RG
     * @param string|null $date Date in YYYY-MM-DD format
     * @return array
     */
    public function getIntradayData($code, string $market = 'RG', ?string $date = null): array
    {
        $codeStr = $this->formatCodes($code);
        $params = [
            'market' => $market,
        ];

        if ($date !== null) {
            $params['date'] = $date;
        }

        return $this->client->get("/batch/intraday-data/{$codeStr}", $params);
    }

    /**
     * Alias for getIntradayData.
     *
     * @param string|array $code Stock code(s)
     * @param string $market Market type
     * @param string|null $date Date
     * @return array
     */
    public function intradayData($code, string $market = 'RG', ?string $date = null): array
    {
        return $this->getIntradayData($code, $market, $date);
    }

    /**
     * Get batch intraday index data for multiple index codes in 1 request.
     *
     * @param string|array $code Index code(s) separated by pipe/comma or array (e.g. 'COMPOSITE|LQ45|IDX30' or ['COMPOSITE', 'LQ45', 'IDX30'])
     * @return array
     */
    public function getIntradayIndex($code): array
    {
        $codeStr = $this->formatCodes($code);
        return $this->client->get("/batch/intraday-index/{$codeStr}");
    }

    /**
     * Alias for getIntradayIndex.
     *
     * @param string|array $code Index code(s)
     * @return array
     */
    public function intradayIndex($code): array
    {
        return $this->getIntradayIndex($code);
    }
}
