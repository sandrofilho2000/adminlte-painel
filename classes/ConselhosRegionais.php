<?php

namespace Classes;

class ConselhosRegionais
{
    private const LEGENDAS = [
        'RJ' => 'CREF1/RJ',
        'RS' => 'CREF2/RS',
        'SC' => 'CREF3/SC',
        'SP' => 'CREF4/SP',
        'CE' => 'CREF5/CE',
        'MG' => 'CREF6/MG',
        'DF' => 'CREF7/DF',
        'AM' => 'CREF8/AM-AC-RO-RR',
        'PR' => 'CREF9/PR',
        'PB' => 'CREF10/PB',
        'MS' => 'CREF11/MS',
        'PE' => 'CREF12/PE',
        'BA' => 'CREF13/BA',
        'GO' => 'CREF14/GO-TO',
        'PI' => 'CREF15/PI',
        'RN' => 'CREF16/RN',
        'MT' => 'CREF17/MT',
        'PA' => 'CREF18/PA-AP',
        'AL' => 'CREF19/AL',
        'SE' => 'CREF20/SE',
        'MA' => 'CREF21/MA',
        'ES' => 'CREF22/ES',
    ];

    public static function listar(): array
    {
        return self::LEGENDAS;
    }

    public static function obterLegenda($estadoConselho): string
    {
        $estadoConselho = strtoupper(trim((string) $estadoConselho));

        return self::LEGENDAS[$estadoConselho] ?? $estadoConselho;
    }

    public static function existe($estadoConselho): bool
    {
        $estadoConselho = strtoupper(trim((string) $estadoConselho));

        return isset(self::LEGENDAS[$estadoConselho]);
    }
}
