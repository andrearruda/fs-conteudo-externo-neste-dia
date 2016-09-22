<?php

namespace App\Action\Ephemeris;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Thapp\XmlBuilder\XMLBuilder;
use Thapp\XmlBuilder\Normalizer;

final class YearlyAction
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $data = array();

        for($i = 0; $i < 12; $i++)
        {
            $dt = \DateTime::createFromFormat('Y-m-d', date('Y-' . str_pad(($i + 1), 2, '0', STR_PAD_LEFT) . '-01'));
            $date = strtotime($dt->format('Y-m-d'));
            $dir = __DIR__ . '/../../../../data/ephemeris/daily/' . utf8_encode(strftime('%B', $date));

            $data[$i] = array(
                'month' => utf8_encode(strftime('%B', strtotime($dt->format('Y-m-d')))),
                'days' => ''
            );

            for($j = 1; $j <= date('t', $date); $j++)
            {
                $file = $dir . '/' . str_pad($j, 2, '0', STR_PAD_LEFT) . '.json';
                if(file_exists($file))
                {
                    $data[$i]['days'][] = json_decode(file_get_contents($file), true);
                }
            }
        }

        $xmlBuilder = new XmlBuilder('root');
        $xmlBuilder->setSingularizer(function ($name) {
            if ('days' === $name) {
                return 'day';
            }
            if ('itens' === $name) {
                return 'item';
            }
            return $name;
        });
        $xmlBuilder->load($data);
        $xml_output = $xmlBuilder->createXML(true);

        $response->write($xml_output);
        $response = $response->withHeader('content-type', 'text/xml');
        return $response;
    }
}