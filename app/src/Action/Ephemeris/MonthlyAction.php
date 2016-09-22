<?php

namespace App\Action\Ephemeris;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Thapp\XmlBuilder\XMLBuilder;
use Thapp\XmlBuilder\Normalizer;

final class MonthlyAction
{
	private $date, $dirFilesCachedByMonthly;

	public function __invoke(Request $request, Response $response, $args)
	{
		$month = (int) (isset($args['month']) ? $args['month'] : date('m'));

		$str_dt = date('Y') . '-' . $month . '-1';
		$dt = \DateTime::createFromFormat('Y-m-d', $str_dt);

		if(\DateTime::getLastErrors()['warning_count'] > 0)
		{
			$data = array(
				'status' => 'error',
				'message' => \DateTime::getLastErrors()['warnings']
			);
		}
		else
		{
			$this->setDate(strtotime($dt->format('Y-m-d')));
			$this->setDirFilesCachedByMonthly(__DIR__ . '/../../../../data/ephemeris/daily/' . strftime('%B', $this->getDate()));

			$data = array(
				'month' => utf8_encode(ucfirst(strftime('%B', $this->getDate()))),
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


}
