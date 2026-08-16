<?php

namespace App\Utils;

class CashDenominationUtil
{
    public static function fromSettings(array $settings): array
    {
        if (! empty($settings['cash_denominations_v2']) && is_array($settings['cash_denominations_v2'])) {
            return self::normalize($settings['cash_denominations_v2']);
        }

        $legacy = array_filter(array_map('trim', explode(',', $settings['cash_denominations'] ?? '')));
        $items = [];
        foreach ($legacy as $label) {
            preg_match('/\d+(?:\.\d+)?/', str_replace(',', '', $label), $match);
            $value = isset($match[0]) ? (float) $match[0] : 0;
            $is_pesewa = (bool) preg_match('/\d+(?:\.\d+)?\s*p(?:esewa)?$/i', $label);
            if ($is_pesewa && $value >= 1) {
                $value /= 100;
            }
            $type = preg_match('/coin|pence|pesewa/i', $label) || $is_pesewa || ($value === 2.0 && is_numeric($label)) || $value < 1 ? 'coin' : 'note';
            $items[] = compact('label', 'value', 'type');
        }

        return self::normalize($items);
    }

    public static function normalize(array $items): array
    {
        $normalized = [];
        $used_codes = [];
        foreach ($items as $item) {
            $label = trim((string) ($item['label'] ?? ''));
            $value = round((float) ($item['value'] ?? 0), 4);
            $type = ($item['type'] ?? 'note') === 'coin' ? 'coin' : 'note';
            if ($label === '' || $value <= 0) {
                continue;
            }
            $base_code = strtoupper(trim((string) ($item['code'] ?? '')));
            if ($base_code === '') {
                $base_code = 'GHS_'.str_replace('.', '_', rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.')).'_'.strtoupper($type);
            }
            $code = preg_replace('/[^A-Z0-9_\-]/', '_', $base_code);
            $candidate = $code;
            $suffix = 2;
            while (isset($used_codes[$candidate])) {
                $candidate = $code.'_'.$suffix++;
            }
            $code = $candidate;
            $used_codes[$code] = true;
            $normalized[] = ['code' => $code, 'label' => $label, 'value' => $value, 'type' => $type];
        }

        usort($normalized, fn ($a, $b) => [$a['type'] === 'coin', -$a['value']] <=> [$b['type'] === 'coin', -$b['value']]);

        return $normalized;
    }

    public static function byCode(array $settings): array
    {
        $result = [];
        foreach (self::fromSettings($settings) as $item) {
            $result[$item['code']] = $item;
        }

        return $result;
    }

    public static function submittedRecords(array $settings, array $submitted): array
    {
        $configured = self::byCode($settings);
        $records = [];
        foreach ($submitted as $key => $count) {
            $count = max(0, (int) $count);
            if ($count === 0) {
                continue;
            }
            $item = $configured[$key] ?? null;
            if (! $item && is_numeric($key)) {
                $item = collect($configured)->first(fn ($candidate) => abs($candidate['value'] - (float) $key) < 0.00001);
            }
            if (! $item) {
                continue;
            }
            $records[] = [
                'amount' => $item['value'],
                'denomination_code' => $item['code'],
                'denomination_label' => $item['label'],
                'denomination_type' => $item['type'],
                'total_count' => $count,
            ];
        }

        return $records;
    }
}
