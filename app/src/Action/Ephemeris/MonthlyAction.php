<?php

namespace App\Action\Ephemeris;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Thapp\XmlBuilder\XMLBuilder,
    Thapp\XmlBuilder\Normalizer;

use Stringy\Stringy as S;

final class MonthlyAction
{
	private $date, $month, $dirFilesCachedByMonthly;

	public function __invoke(Request $request, Response $response, $args)
	{
        if(isset($args['month']))
        {
            $this->setMonth($args['month']);
        }

        $this->setDate(strtotime(date('Y-' . $this->getMonth() . '-01')));

        $month_name = strftime('%B', $this->getDate());
        $month_name = mb_check_encoding($month_name, 'UTF-8') ? $month_name : utf8_encode($month_name);

        $this->setDirFilesCachedByMonthly(__DIR__ . '/../../../../data/ephemeris/daily/' . (string) S::create($month_name)->slugify());

        $data = array(
            'month' => (string) S::create($month_name)->titleize(),
            'days' => array()
        );

        for($i = 1; $i <= date('t', $this->getDate()); $i++)
        {
            $file = $this->getDirFilesCachedByMonthly() . '/' . str_pad($i, 2, '0', STR_PAD_LEFT) . '.json';
            if(file_exists($file))
            {
                $data['days'][] = json_decode(file_get_contents($file), true);
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
        $response = $response->withHeader('content-type', 'application/xml; charset=utf-8');
        return $response;
	}

	private function setDate($date)
	{
		$this->date = $date;
	}

	private function getDate()
	{
		return $this->date;
	}

	public function getDirFilesCachedByMonthly()
	{
		return $this->dirFilesCachedByMonthly;
	}

	public function setDirFilesCachedByMonthly($dirFilesCachedByMonthly)
	{
		$this->dirFilesCachedByMonthly = $dirFilesCachedByMonthly;
	}

    /**
     * @return mixed
     */
    public function getMonth()
    {
        return empty($this->month) ? date('m') : $this->month;
    }

    /**
     * @param mixed $month
     */
    public function setMonth($month)
    {
        $this->month = ($month >= 1 && $month <= 12) ? $month : date('m');
    }
}
