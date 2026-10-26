<?php

declare(strict_types=1);

namespace Invezgo\Tests;

use Invezgo\Model\IntradayIndexResponse;
use Invezgo\Model\ScreenerScreenItem;
use Invezgo\Model\ShareholderRelationResponse;
use Invezgo\Model\StockRunningTradeData;
use Invezgo\Model\TopChangeResponse;
use PHPUnit\Framework\TestCase;

final class ModelTest extends TestCase
{
    public function testHydratesNestedModelsAndRoundTrips(): void
    {
        $data = [
            'gain' => [[
                'code' => 'BBCA', 'name' => 'Bank Central Asia', 'price' => 9875, 'change' => 1.5,
                'value' => 1.0e12, 'volume' => 85213400, 'logo' => null, 'calculated_value' => 1.5,
                'graph' => [['date' => '2024-12-30', 'value' => 1.5]],
            ]],
            'loss' => [],
        ];
        $model = TopChangeResponse::fromArray($data);

        self::assertSame('BBCA', $model->gain[0]->code);
        self::assertSame(9875, $model->gain[0]->price);
        self::assertSame('2024-12-30', $model->gain[0]->graph[0]->date);
        self::assertSame([], $model->loss);
        self::assertSame($model->toArray(), json_decode((string) json_encode($model), true));
        self::assertSame('BBCA', $model->toArray()['gain'][0]['code']);
    }

    public function testMissingAndNullFieldsFallBackToDefaults(): void
    {
        $model = TopChangeResponse::fromArray(['gain' => null]);
        self::assertSame([], $model->gain);
        self::assertSame([], $model->loss);
    }

    public function testFlexibleNumbers(): void
    {
        $trade = StockRunningTradeData::fromArray(['buy_order_id' => '123456789012345678', 'sell_order_id' => 42, 'price' => '9875']);
        self::assertSame(123456789012345678, $trade->buy_order_id);
        self::assertSame(42, $trade->sell_order_id);
        self::assertSame(9875.0, $trade->price);

        $index = IntradayIndexResponse::fromArray(['code' => 'COMPOSITE', 'open' => '7100.5', 'close' => 7110]);
        self::assertSame(7100.5, $index->open);
        self::assertSame(7110.0, $index->close);
        self::assertNull($index->high);
    }

    public function testAdditionalPropertiesAreCollected(): void
    {
        $row = ScreenerScreenItem::fromArray(['code' => 'BBCA', 'matched' => true, 'close' => 9875, 'rsi(14)' => null]);
        self::assertSame('BBCA', $row->code);
        self::assertTrue($row->matched);
        self::assertSame(['close' => 9875.0, 'rsi(14)' => null], $row->values);
        self::assertSame(['code' => 'BBCA', 'matched' => true, 'close' => 9875.0, 'rsi(14)' => null], $row->toArray());
    }

    public function testMergedUnionNodes(): void
    {
        $graph = ShareholderRelationResponse::fromArray([
            'nodes' => [
                ['id' => 'stock:BBCA', 'type' => 'stock', 'kind' => 'root', 'label' => 'BBCA', 'root' => true, 'depth' => 0,
                    'percentage' => null, 'code' => 'BBCA', 'company_name' => 'Bank Central Asia', 'logo' => null],
                ['id' => 'entity:DJARUM', 'type' => 'entity', 'kind' => 'corporate', 'label' => 'Djarum', 'root' => false, 'depth' => 1,
                    'percentage' => 54.94, 'name' => 'PT Dwimuria Investama Andalan'],
            ],
            'edges' => [],
        ]);
        self::assertSame('stock', $graph->nodes[0]->type);
        self::assertSame('BBCA', $graph->nodes[0]->code);
        self::assertSame('entity', $graph->nodes[1]->type);
        self::assertSame('PT Dwimuria Investama Andalan', $graph->nodes[1]->name);
        self::assertNull($graph->nodes[1]->code);
        self::assertSame(54.94, $graph->nodes[1]->percentage);
    }
}
