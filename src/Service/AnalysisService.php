<?php

namespace Invezgo\Service;

/**
 * Analysis Service - Data Saham Indonesia
 */
class AnalysisService extends BaseService
{
    /**
     * Get list of all listed companies in BEI
     *
     * @return array
     */
    public function getStockList(): array
    {
        return $this->client->get('/analysis/list/stock');
    }

    /**
     * Get list of all brokers/securities in BEI
     *
     * @return array
     */
    public function getBrokerList(): array
    {
        return $this->client->get('/analysis/list/broker');
    }

    /**
     * Get list of all indexes in BEI
     *
     * @return array
     */
    public function getIndexList(): array
    {
        return $this->client->get('/analysis/list/index');
    }

    /**
     * Get complete company information
     *
     * @param string $code Stock code (4-6 characters)
     * @return array
     */
    public function information(string $code): array
    {
        return $this->client->get("/analysis/information/{$code}");
    }

    /**
     * Get top gainer & loser daily
     *
     * @param string $date Date in YYYY-MM-DD format (use working day for best results)
     * @return array
     */
    public function topChange(string $date): array
    {
        return $this->client->get('/analysis/top/change', ['date' => $date]);
    }

    /**
     * Get top foreign accumulation & distribution
     *
     * @param string $date Date in YYYY-MM-DD format (use working day for best results)
     * @return array
     */
    public function topForeign(string $date): array
    {
        return $this->client->get('/analysis/top/foreign', ['date' => $date]);
    }

    /**
     * Get top bandarmologi accumulation & distribution
     *
     * @param string $date Date in YYYY-MM-DD format (use working day for best results)
     * @return array
     */
    public function topAccumulation(string $date): array
    {
        return $this->client->get('/analysis/top/accumulation', ['date' => $date]);
    }

    /**
     * Get top retail accumulation & distribution
     *
     * @param string $date Date in YYYY-MM-DD format (use working day for best results)
     * @return array
     */
    public function topRitel(string $date): array
    {
        return $this->client->get('/analysis/top/ritel', ['date' => $date]);
    }

    /**
     * Get intraday chart data
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $market Market type: RG (Reguler), NG (Negotiated), TN (Tunai). Default: RG
     * @return array
     */
    public function getIntradayChart(string $code, string $market = 'RG'): array
    {
        return $this->client->get("/analysis/intraday/{$code}", ['market' => $market]);
    }

    /**
     * Get intraday data
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $market Market type: RG (Reguler), NG (Negotiated), TN (Tunai)
     * @return array
     */
    public function getIntradayData(string $code, string $market = 'RG'): array
    {
        return $this->client->get("/analysis/intraday-data/{$code}", ['market' => $market]);
    }

    /**
     * Get intraday index data
     *
     * @param string $code Index code (e.g., COMPOSITE, LQ45)
     * @param string $market Market type: RG (Reguler), NG (Negotiated), TN (Tunai)
     * @return array
     */
    public function getIntradayIndex(string $code, string $market = 'RG'): array
    {
        return $this->client->get("/analysis/intraday-index/{$code}", ['market' => $market]);
    }

    /**
     * Get order book data
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $market Market type: RG (Reguler), NG (Negotiated), TN (Tunai)
     * @return array
     */
    public function getOrderBook(string $code, string $market = 'RG'): array
    {
        return $this->client->get("/analysis/order-book/{$code}", ['market' => $market]);
    }

    /**
     * Get complete stock price chart
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $from Start date (YYYY-MM-DD format)
     * @param string $to End date (YYYY-MM-DD format)
     * @return array
     */
    public function getAdvanceChart(string $code, string $from, string $to): array
    {
        return $this->client->get("/analysis/chart/stock/{$code}", [
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * Get index chart
     *
     * @param string $code Index code (e.g., COMPOSITE, IDXCYCLIC, LQ45)
     * @param string $from Start date (YYYY-MM-DD format)
     * @param string $to End date (YYYY-MM-DD format)
     * @return array
     */
    public function getIndexChart(string $code, string $from, string $to): array
    {
        return $this->client->get("/analysis/chart/index/{$code}", [
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * Get indicator chart data
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $indicator Indicator type (bdm, foreign, ritel, ratio)
     * @param string $from Start date (YYYY-MM-DD format)
     * @param string $to End date (YYYY-MM-DD format)
     * @return array
     */
    public function getIndicatorChart(string $code, string $indicator, string $from, string $to): array
    {
        return $this->client->get("/analysis/chart/stock/{$indicator}/{$code}", [
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * Get sector stalker data
     *
     * @param string $from Start date (YYYY-MM-DD format)
     * @param string $to End date (YYYY-MM-DD format)
     * @param string|null $base Base index for filter (default: COMPOSITE)
     * @param int|null $limit Limit number of results
     * @param string|null $filter Filter for stock code/name
     * @param string|null $filterColumn Column to filter (change, value, volume, foreign, freq, bdm, ritel, ratio, open, high, low, close)
     * @param string|null $filterOperator Filter operator (<, >, =, >=, <=, !=)
     * @param float|null $filterValue Filter value
     * @return array
     */
    public function sectorStalker(
        string $from,
        string $to,
        ?string $base = null,
        ?int $limit = null,
        ?string $filter = null,
        ?string $filterColumn = null,
        ?string $filterOperator = null,
        ?float $filterValue = null
    ): array {
        $params = [
            'from' => $from,
            'to' => $to,
        ];

        if ($base !== null) {
            $params['base'] = $base;
        }
        if ($limit !== null) {
            $params['limit'] = $limit;
        }
        if ($filter !== null) {
            $params['filter'] = $filter;
        }
        if ($filterColumn !== null) {
            $params['filter_column'] = $filterColumn;
        }
        if ($filterOperator !== null) {
            $params['filter_operator'] = $filterOperator;
        }
        if ($filterValue !== null) {
            $params['filter_value'] = $filterValue;
        }

        return $this->client->get('/analysis/stalker/sector', $params);
    }

    /**
     * Get sector rotation chart
     *
     * @param string $from Start date (YYYY-MM-DD format)
     * @param string $to End date (YYYY-MM-DD format)
     * @param string|null $base Benchmark index (default: COMPOSITE)
     * @param int|null $length Smoothing period (default: 10, range: 5-50)
     * @param int|null $tail Trailing points (default: 5, range: 1-20)
     * @param int|null $limit Limit number of results
     * @param string|null $filter Filter for stock code/name
     * @param string|null $filterColumn Column to filter
     * @param string|null $filterOperator Filter operator
     * @param float|null $filterValue Filter value
     * @return array
     */
    public function sectorRotation(
        string $from,
        string $to,
        ?string $base = null,
        ?int $length = null,
        ?int $tail = null,
        ?int $limit = null,
        ?string $filter = null,
        ?string $filterColumn = null,
        ?string $filterOperator = null,
        ?float $filterValue = null
    ): array {
        $params = [
            'from' => $from,
            'to' => $to,
        ];

        if ($base !== null) {
            $params['base'] = $base;
        }
        if ($length !== null) {
            $params['length'] = $length;
        }
        if ($tail !== null) {
            $params['tail'] = $tail;
        }
        if ($limit !== null) {
            $params['limit'] = $limit;
        }
        if ($filter !== null) {
            $params['filter'] = $filter;
        }
        if ($filterColumn !== null) {
            $params['filter_column'] = $filterColumn;
        }
        if ($filterOperator !== null) {
            $params['filter_operator'] = $filterOperator;
        }
        if ($filterValue !== null) {
            $params['filter_value'] = $filterValue;
        }

        return $this->client->get('/analysis/sector/rotation', $params);
    }

    /**
     * Get broker stalker data
     *
     * @param string $broker Broker code(s) - single or multiple comma separated
     * @param string $stock Stock code
     * @param string $from Start date (YYYY-MM-DD format)
     * @param string $to End date (YYYY-MM-DD format)
     * @param string $investor Investor type: all, f (foreign), d (domestic)
     * @param string $market Market type: RG, NG, TN
     * @param string $scope Scope: volume, value
     * @return array
     */
    public function brokerStalker(
        string $broker,
        string $stock,
        string $from,
        string $to,
        string $investor = 'all',
        string $market = 'RG',
        string $scope = 'value'
    ): array {
        return $this->client->get("/analysis/stalker/broker/{$broker}/{$stock}", [
            'from' => $from,
            'to' => $to,
            'investor' => $investor,
            'market' => $market,
            'scope' => $scope,
        ]);
    }

    /**
     * Get broker stalker list
     *
     * @param string $code Broker code(s) - single or multiple comma separated
     * @param string $from Start date (YYYY-MM-DD format)
     * @param string $to End date (YYYY-MM-DD format)
     * @param string $investor Investor type: all, f (foreign), d (domestic)
     * @param string $scope Scope: volume, value
     * @param string $market Market type: RG, NG, TN
     * @return array
     */
    public function brokerStalkerList(
        string $code,
        string $from,
        string $to,
        string $investor = 'all',
        string $scope = 'value',
        string $market = 'RG'
    ): array {
        return $this->client->get("/analysis/stalker/list/{$code}", [
            'from' => $from,
            'to' => $to,
            'investor' => $investor,
            'scope' => $scope,
            'market' => $market,
        ]);
    }

    /**
     * Get shareholder detail data (5%)
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $name Shareholder name
     * @return array
     */
    public function shareholderDetail(string $code, string $name): array
    {
        return $this->client->get("/analysis/shareholder-detail/{$code}", [
            'code' => $code,
            'name' => $name,
        ]);
    }

    /**
     * Get shareholder detail one (1%)
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $name Shareholder name
     * @return array
     */
    public function shareholderDetailOne(string $code, string $name): array
    {
        return $this->client->get('/analysis/shareholder-detail-one', [
            'code' => $code,
            'name' => $name,
        ]);
    }

    /**
     * Get shareholder number
     *
     * @param string $code Stock code (4-6 characters)
     * @return array
     */
    public function shareholderNumber(string $code): array
    {
        return $this->client->get("/analysis/shareholder/number/{$code}");
    }

    /**
     * Get shareholder relation graph
     *
     * @param string|null $code Stock code (optional)
     * @param string|null $name Shareholder name (optional)
     * @param int|null $depth Graph depth (1-4)
     * @param int|null $maxNodes Maximum nodes limit
     * @param int|null $neighbors Maximum relations per node
     * @param float|null $minPercentage Minimum ownership percentage filter
     * @return array
     */
    public function shareholderRelation(
        ?string $code = null,
        ?string $name = null,
        ?int $depth = null,
        ?int $maxNodes = null,
        ?int $neighbors = null,
        ?float $minPercentage = null
    ): array {
        $params = [];

        if ($code !== null) {
            $params['code'] = $code;
        }
        if ($name !== null) {
            $params['name'] = $name;
        }
        if ($depth !== null) {
            $params['depth'] = $depth;
        }
        if ($maxNodes !== null) {
            $params['max_nodes'] = $maxNodes;
        }
        if ($neighbors !== null) {
            $params['neighbors'] = $neighbors;
        }
        if ($minPercentage !== null) {
            $params['min_percentage'] = $minPercentage;
        }

        return $this->client->get('/analysis/shareholder/relation', $params);
    }

    /**
     * Get shareholder composition
     *
     * @param string $code Stock code (4-6 characters)
     * @return array
     */
    public function shareholder(string $code): array
    {
        return $this->client->get("/analysis/shareholder/{$code}");
    }

    /**
     * Get KSEI shareholder data
     *
     * @param string $code Stock code (4-6 characters)
     * @param int $range Number of months (1-120)
     * @return array
     */
    public function shareholderKSEI(string $code, int $range = 6): array
    {
        return $this->client->get("/analysis/shareholder/ksei/{$code}", ['range' => $range]);
    }

    /**
     * Get broker summary per stock
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $from Start date (YYYY-MM-DD format)
     * @param string $to End date (YYYY-MM-DD format)
     * @param string $investor Investor type: all, f (foreign), d (domestic)
     * @param string $market Market type: RG (Reguler), NG (Negotiated), TN (Tunai)
     * @return array
     */
    public function summaryStock(string $code, string $from, string $to, string $investor = 'all', string $market = 'RG'): array
    {
        return $this->client->get("/analysis/summary/stock/{$code}", [
            'from' => $from,
            'to' => $to,
            'investor' => $investor,
            'market' => $market,
        ]);
    }

    /**
     * Get broker summary per broker
     *
     * @param string $code Broker code (2 characters) - can be multiple comma separated
     * @param string $from Start date (YYYY-MM-DD format)
     * @param string $to End date (YYYY-MM-DD format)
     * @param string $investor Investor type: all, f (foreign), d (domestic)
     * @param string $market Market type: RG (Reguler), NG (Negotiated), TN (Tunai)
     * @return array
     */
    public function summaryBroker(string $code, string $from, string $to, string $investor = 'all', string $market = 'RG'): array
    {
        return $this->client->get("/analysis/summary/broker/{$code}", [
            'from' => $from,
            'to' => $to,
            'investor' => $investor,
            'market' => $market,
        ]);
    }

    /**
     * Get broker summary chart for stock
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $from Start date (YYYY-MM-DD format)
     * @param string $to End date (YYYY-MM-DD format)
     * @param string $scope Calculation component: volume, value, freq
     * @param string $market Market type: RG (Reguler), NG (Negotiated), TN (Tunai)
     * @return array
     */
    public function summaryStockChart(string $code, string $from, string $to, string $scope, string $market = 'RG'): array
    {
        return $this->client->get("/analysis/summary-chart/stock/{$code}", [
            'from' => $from,
            'to' => $to,
            'scope' => $scope,
            'market' => $market,
        ]);
    }

    /**
     * Get broker summary chart for broker
     *
     * @param string $code Broker code (2 characters) - can be multiple comma separated
     * @param string $from Start date (YYYY-MM-DD format)
     * @param string $to End date (YYYY-MM-DD format)
     * @param string $scope Calculation component: volume, value, freq
     * @param string $market Market type: RG (Reguler), NG (Negotiated), TN (Tunai)
     * @return array
     */
    public function summaryBrokerChart(string $code, string $from, string $to, string $scope, string $market = 'RG'): array
    {
        return $this->client->get("/analysis/summary-chart/broker/{$code}", [
            'from' => $from,
            'to' => $to,
            'scope' => $scope,
            'market' => $market,
        ]);
    }

    /**
     * Get inventory chart for stock
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $from Start date (YYYY-MM-DD format)
     * @param string $to End date (YYYY-MM-DD format)
     * @param string $scope Calculation component: vol, val, freq
     * @param string $investor Investor type: all, f (foreign), d (domestic)
     * @param int $limit Number of brokers to display (max 20)
     * @param string $market Market type: ALL, RG (Reguler), NG (Negotiated), TN (Tunai)
     * @param string|null $filter Broker codes to filter (comma separated)
     * @param string|null $filterOperator Filter operator (<, >, =, >=, <=, !=)
     * @param float|null $filterValue Filter value
     * @return array
     */
    public function inventoryStockChart(
        string $code,
        string $from,
        string $to,
        string $scope,
        string $investor = 'all',
        int $limit = 5,
        string $market = 'RG',
        ?string $filter = null,
        ?string $filterOperator = null,
        ?float $filterValue = null
    ): array {
        $params = [
            'from' => $from,
            'to' => $to,
            'scope' => $scope,
            'investor' => $investor,
            'limit' => $limit,
            'market' => $market,
        ];

        if ($filter !== null) {
            $params['filter'] = $filter;
        }
        if ($filterOperator !== null) {
            $params['filter_operator'] = $filterOperator;
        }
        if ($filterValue !== null) {
            $params['filter_value'] = $filterValue;
        }

        return $this->client->get("/analysis/inventory-chart/stock/{$code}", $params);
    }

    /**
     * Get inventory chart for broker
     *
     * @param string $code Broker code (2 characters) - can be multiple comma separated
     * @param string $from Start date (YYYY-MM-DD format)
     * @param string $to End date (YYYY-MM-DD format)
     * @param string $scope Calculation component: vol, val, freq
     * @param string $investor Investor type: all, f (foreign), d (domestic)
     * @param int $limit Number of stocks to display (max 20)
     * @param string $market Market type: ALL, RG (Reguler), NG (Negotiated), TN (Tunai)
     * @param string|null $filter Stock codes to filter (comma separated)
     * @param string|null $filterOperator Filter operator (<, >, =, >=, <=, !=)
     * @param float|null $filterValue Filter value
     * @return array
     */
    public function inventoryBrokerChart(
        string $code,
        string $from,
        string $to,
        string $scope,
        string $investor = 'all',
        int $limit = 5,
        string $market = 'RG',
        ?string $filter = null,
        ?string $filterOperator = null,
        ?float $filterValue = null
    ): array {
        $params = [
            'from' => $from,
            'to' => $to,
            'scope' => $scope,
            'investor' => $investor,
            'limit' => $limit,
            'market' => $market,
        ];

        if ($filter !== null) {
            $params['filter'] = $filter;
        }
        if ($filterOperator !== null) {
            $params['filter_operator'] = $filterOperator;
        }
        if ($filterValue !== null) {
            $params['filter_value'] = $filterValue;
        }

        return $this->client->get("/analysis/inventory-chart/broker/{$code}", $params);
    }

    /**
     * Get momentum chart
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $date Date in YYYY-MM-DD format (use working day for best results)
     * @param int $range Time interval in minutes (5, 10, 15, 30, 60)
     * @param string $scope Calculation component: value, volume
     * @return array
     */
    public function momentumChart(string $code, string $date, int $range, string $scope): array
    {
        return $this->client->get("/analysis/momentum-chart/{$code}", [
            'date' => $date,
            'range' => $range,
            'scope' => $scope,
        ]);
    }

    /**
     * Get intraday inventory chart
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $date Date in YYYY-MM-DD format
     * @param int $range Time interval in minutes (5, 10, 15, 30, 60)
     * @param string $type Type: value, volume
     * @param int $total Total number of data to display
     * @param string $buyer Filter buyer: ALL, F, D
     * @param string $seller Filter seller: ALL, F, D
     * @param string $market Market type: RG, NG, TN
     * @param string|null $filter Additional filter
     * @return array
     */
    public function intradayInventoryChart(
        string $code,
        string $date,
        int $range,
        string $type,
        int $total,
        string $buyer,
        string $seller,
        string $market = 'RG',
        ?string $filter = null
    ): array {
        $params = [
            'date' => $date,
            'range' => $range,
            'type' => $type,
            'total' => $total,
            'buyer' => $buyer,
            'seller' => $seller,
            'market' => $market,
        ];

        if ($filter !== null) {
            $params['filter'] = $filter;
        }

        return $this->client->get("/analysis/intraday-inventory-chart/{$code}", $params);
    }

    /**
     * Get sankey chart (crossing)
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $date Date in YYYY-MM-DD format
     * @param string $type Type: value, volume
     * @param string $buyer Filter buyer: ALL, F, D
     * @param string $seller Filter seller: ALL, F, D
     * @param string $market Market type: RG, NG, TN
     * @return array
     */
    public function sankeyChart(string $code, string $date, string $type, string $buyer, string $seller, string $market = 'RG'): array
    {
        return $this->client->get("/analysis/sankey-chart/{$code}", [
            'date' => $date,
            'type' => $type,
            'buyer' => $buyer,
            'seller' => $seller,
            'market' => $market,
        ]);
    }

    /**
     * Get price table
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $date Date in YYYY-MM-DD format (use working day for best results)
     * @return array
     */
    public function priceTable(string $code, string $date): array
    {
        return $this->client->get("/analysis/price-table/{$code}", ['date' => $date]);
    }

    /**
     * Get time table
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $date Date in YYYY-MM-DD format (use working day for best results)
     * @param int $range Time interval in minutes (5, 10, 15, 30, 60)
     * @return array
     */
    public function timeTable(string $code, string $date, int $range): array
    {
        return $this->client->get("/analysis/time-table/{$code}", [
            'date' => $date,
            'range' => $range,
        ]);
    }

    /**
     * Get price diary (daily price changes)
     *
     * @param string $code Stock code (4-6 characters)
     * @return array
     */
    public function priceDiary(string $code): array
    {
        return $this->client->get("/analysis/price-diary/{$code}");
    }

    /**
     * Get price seasonality (monthly price changes)
     *
     * @param string $code Stock code (4-6 characters)
     * @param int $range Number of months (1-120)
     * @return array
     */
    public function priceSeasonality(string $code, int $range = 12): array
    {
        return $this->client->get("/analysis/price-seasonality/{$code}", ['range' => $range]);
    }

    /**
     * Get shareholder above 5%
     *
     * @param string $from Start date (YYYY-MM-DD format)
     * @param string $to End date (YYYY-MM-DD format)
     * @param int $page Page number (starts from 1)
     * @param int $limit Number of data per page (max 100)
     * @param string|null $code Stock code filter
     * @param string|null $name Shareholder name filter
     * @param string|null $broker Broker codes filter
     * @return array
     */
    public function shareholderAbove(
        string $from,
        string $to,
        int $page = 1,
        int $limit = 10,
        ?string $code = null,
        ?string $name = null,
        ?string $broker = null
    ): array {
        $params = [
            'from' => $from,
            'to' => $to,
            'page' => $page,
            'limit' => $limit,
        ];

        if ($code !== null) {
            $params['code'] = $code;
        }
        if ($name !== null) {
            $params['name'] = $name;
        }
        if ($broker !== null) {
            $params['broker'] = $broker;
        }

        return $this->client->get('/analysis/shareholder-above', $params);
    }

    /**
     * Get shareholder above 5% chart
     *
     * @param string $code Stock code
     * @param string $name Shareholder name
     * @param string|null $broker Broker code or "INSTITUSI"
     * @param string|null $date Date
     * @return array
     */
    public function shareholderAboveChart(string $code, string $name, ?string $broker = null, ?string $date = null): array
    {
        $params = [
            'name' => $name,
        ];

        if ($broker !== null) {
            $params['broker'] = $broker;
        }
        if ($date !== null) {
            $params['date'] = $date;
        }

        return $this->client->get("/analysis/shareholder-above-chart/{$code}", $params);
    }

    /**
     * Get shareholder one (1%)
     *
     * @param string $from Start date (YYYY-MM-DD format)
     * @param string $to End date (YYYY-MM-DD format)
     * @param int $page Page number (starts from 1)
     * @param int $limit Number of data per page (max 100)
     * @param string|null $code Stock code filter
     * @param string|null $name Shareholder name filter
     * @return array
     */
    public function shareholderOne(
        string $from,
        string $to,
        int $page = 1,
        int $limit = 10,
        ?string $code = null,
        ?string $name = null
    ): array {
        $params = [
            'from' => $from,
            'to' => $to,
            'page' => $page,
            'limit' => $limit,
        ];

        if ($code !== null) {
            $params['code'] = $code;
        }
        if ($name !== null) {
            $params['name'] = $name;
        }

        return $this->client->get('/analysis/shareholder-one', $params);
    }

    /**
     * Get shareholder one chart (1%)
     *
     * @param string $code Stock code
     * @param string $name Shareholder name
     * @param string|null $broker Broker code or "INSTITUSI"
     * @param string|null $date Date
     * @return array
     */
    public function shareholderOneChart(string $code, string $name, ?string $broker = null, ?string $date = null): array
    {
        $params = [
            'name' => $name,
        ];

        if ($broker !== null) {
            $params['broker'] = $broker;
        }
        if ($date !== null) {
            $params['date'] = $date;
        }

        return $this->client->get("/analysis/shareholder-one-chart/{$code}", $params);
    }

    /**
     * Get insider trading data
     *
     * @param string $from Start date (YYYY-MM-DD format)
     * @param string $to End date (YYYY-MM-DD format)
     * @param int $page Page number (starts from 1)
     * @param int $limit Number of data per page (max 100)
     * @param string|null $name Shareholder name filter
     * @param string|null $code Stock code filter
     * @return array
     */
    public function insider(
        string $from,
        string $to,
        int $page = 1,
        int $limit = 10,
        ?string $name = null,
        ?string $code = null
    ): array {
        $params = [
            'from' => $from,
            'to' => $to,
            'page' => $page,
            'limit' => $limit,
        ];

        if ($name !== null) {
            $params['name'] = $name;
        }
        if ($code !== null) {
            $params['code'] = $code;
        }

        return $this->client->get('/analysis/shareholder-insider', $params);
    }

    /**
     * Get insider chart
     *
     * @param string $code Stock code
     * @param string|null $name Shareholder name
     * @param string|null $date Date
     * @return array
     */
    public function insiderChart(string $code, ?string $name = null, ?string $date = null): array
    {
        $params = [];

        if ($name !== null) {
            $params['name'] = $name;
        }
        if ($date !== null) {
            $params['date'] = $date;
        }

        return $this->client->get("/analysis/insider-chart/{$code}", $params);
    }

    /**
     * Get financial statement
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $statement Statement type: BS (Neraca), IS (Laba Rugi), CF (Arus Kas)
     * @param string $type Period type: FY (Tahunan), Q (Kuartalan), Q1-Q4 (Kuartal spesifik)
     * @param int|null $limit Number of periods (max 20)
     * @return array
     */
    public function financialStatement(string $code, string $statement, string $type, ?int $limit = null): array
    {
        $params = [
            'statement' => $statement,
            'type' => $type,
        ];

        if ($limit !== null) {
            $params['limit'] = $limit;
        }

        return $this->client->get("/analysis/financial-statement/{$code}", $params);
    }

    /**
     * Get financial statement chart
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $statement Statement type: BS (Neraca), IS (Laba Rugi), CF (Arus Kas)
     * @param string $type Period type: FY (Tahunan), Q (Kuartalan), Q1-Q4 (Kuartal spesifik)
     * @param string $account Account ID to visualize
     * @param int|null $limit Number of periods (max 20)
     * @return array
     */
    public function financialStatementChart(string $code, string $statement, string $type, string $account, ?int $limit = null): array
    {
        $params = [
            'statement' => $statement,
            'type' => $type,
            'account' => $account,
        ];

        if ($limit !== null) {
            $params['limit'] = $limit;
        }

        return $this->client->get("/analysis/financial-statement-chart/{$code}", $params);
    }

    /**
     * Get key statistics
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $type Period type: FY (Tahunan), Q (Kuartalan), Q1-Q4 (Kuartal spesifik)
     * @param int|null $limit Number of periods (max 20)
     * @return array
     */
    public function keystat(string $code, string $type, ?int $limit = null): array
    {
        $params = [
            'type' => $type,
        ];

        if ($limit !== null) {
            $params['limit'] = $limit;
        }

        return $this->client->get("/analysis/keystat/{$code}", $params);
    }

    /**
     * Get key statistics chart
     *
     * @param string $code Stock code (4-6 characters)
     * @param string $type Period type: FY (Tahunan), Q (Kuartalan), Q1-Q4 (Kuartal spesifik)
     * @param string $name Metric name to visualize
     * @param int|null $limit Number of periods
     * @return array
     */
    public function keystatChart(string $code, string $type, string $name, ?int $limit = null): array
    {
        $params = [
            'type' => $type,
            'name' => $name,
        ];

        if ($limit !== null) {
            $params['limit'] = $limit;
        }

        return $this->client->get("/analysis/keystat-chart/{$code}", $params);
    }

    /**
     * Get corporate action calendar
     *
     * @param string|null $code Stock code filter
     * @param string|null $type Corporate action type (IPO, PUBLIC_EXPOSE, REVERSE, RIGHT, RUPS_RESULT, RUPS_SCHEDULE, SPLIT, WARRANT, BONUS, CONVERTION, DIVIDEND)
     * @param int|null $page Page number (default: 1)
     * @param int|null $limit Number of data per page (max 50, default: 20)
     * @return array
     */
    public function calendar(?string $code = null, ?string $type = null, ?int $page = null, ?int $limit = null): array
    {
        $params = [];

        if ($code !== null) {
            $params['code'] = $code;
        }
        if ($type !== null) {
            $params['type'] = $type;
        }
        if ($page !== null) {
            $params['page'] = $page;
        }
        if ($limit !== null) {
            $params['limit'] = $limit;
        }

        return $this->client->get('/analysis/calendar', $params);
    }
}
