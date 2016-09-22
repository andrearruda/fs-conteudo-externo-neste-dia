<?php

namespace App\Action\Ephemeris;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use phpQuery;

final class DailyAction
{
	private $date, $url, $html, $fileCached;

	public function __invoke(Request $request, Response $response, $args)
	{
		$str_dt = date('Y') . '-' . $args['month'] . '-' . $args['day'];
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

			$dir = __DIR__ . '/../../../../data/ephemeris/daily/' . strftime('%B', $this->getDate());
			if(!file_exists($dir))
			{
				mkdir($dir);
			}

			$this->setFileCached($dir . '/' . strftime('%d', $this->getDate()) . '.json');
			$forceFileCached = isset($request->getQueryParams()['forceFileCached']) ? $request->getQueryParams()['forceFileCached'] : false;

			if(file_exists($this->getFileCached()) && $forceFileCached != true)
			{
				$data = json_decode(file_get_contents($this->getFileCached()));
			}
			else
			{
				$this->setUrl('https://pt.wikipedia.org/wiki/Wikip%C3%A9dia:Efem%C3%A9rides/' . utf8_encode(sprintf('%s_de_%s', (int) strftime('%d', $this->getDate()), strftime('%B', $this->getDate()))));
				$this->setHtml(phpQuery::newDocumentFileHTML($this->getUrl()));

				$data = array(
					'date' => strftime('%Y-%m-%d', $this->getDate()),
					'itens' => array(
						'ephemeris' => array(
							'title' => 'Calendário de ' . strftime('%d %B', $this->getDate()),
							'itens' => array()
						),
						'born' => array(
							'title' => 'Nasceram neste dia',
							'itens' => array()
						),
						'died' => array(
							'title' => 'Morreram neste dia',
							'itens' => array()
						)
					)
				);

				$doc = phpQuery::newDocument($this->getHtml());

				if($doc['#bodyContent #mw-content-text p']->count() > 2)
					$data['itens']['ephemeris']['title'] = $this->removeTextBetweenParentheses(pq($doc['#bodyContent #mw-content-text p:eq(0)'])->text());

				foreach($doc['#bodyContent #mw-content-text ul:eq(0) li'] as $key => $li)
				{
					$data['itens']['ephemeris']['itens'][] = $this->removeTextBetweenParentheses(pq($li)->text());
				}

				foreach($doc['#bodyContent #mw-content-text ul:eq(1) li'] as $key => $li)
				{
					$data['itens']['born']['itens'][] = $this->removeTextBetweenParentheses(pq($li)->text());
				}

				foreach($doc['#bodyContent #mw-content-text ul:eq(2) li'] as $key => $li)
				{
					$data['itens']['died']['itens'][] = $this->removeTextBetweenParentheses(pq($li)->text());
				}

				file_put_contents($this->getFileCached(), json_encode($data));
			}
		}

		$response->write(json_encode($data));
		$response = $response->withHeader('content-type', 'application/json');
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

	public function setUrl($url)
	{
		$this->url = $url;
	}

	private function getUrl()
	{
		return $this->url;
	}

	private function setHtml($html)
	{
		$this->html = $html;
	}

	private function getHtml()
	{
		return $this->html;
	}

	private function getFileCached()
	{
		return $this->fileCached;
	}

	private function setFileCached($fileCached)
	{
		$this->fileCached = $fileCached;
	}

	private function removeTextBetweenParentheses($string)
	{
		return preg_replace('~[\r\n]+~', '', preg_replace('/ \([^)]+\)/', '', $string));
	}
}
?>
