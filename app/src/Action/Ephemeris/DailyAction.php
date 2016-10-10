<?php

namespace App\Action\Ephemeris;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use phpQuery;

use Stringy\Stringy as S;

final class DailyAction
{
	private $date, $day, $month, $url, $html, $fileCached, $dirFilesCachedByMonthly;

	public function __invoke(Request $request, Response $response, $args)
	{
		$this->setDay($args['day']);
		$this->setMonth($args['month']);

		$this->setDate(strtotime(date('Y-' . $this->getMonth() . '-' . $this->getDay())));

		$month_name = strftime('%B', $this->getDate());
		$month_name = mb_check_encoding($month_name, 'UTF-8') ? $month_name : utf8_encode($month_name);

		$this->setDirFilesCachedByMonthly(__DIR__ . '/../../../../data/ephemeris/daily/' . (string) S::create($month_name)->slugify());
		if(!file_exists($this->getDirFilesCachedByMonthly()))
		{
			mkdir($this->getDirFilesCachedByMonthly());
		}

		$this->setFileCached($this->getDirFilesCachedByMonthly() . '/' . strftime('%d', $this->getDate()) . '.json');

		$forceFileCached = isset($request->getQueryParams()['forceFileCached']) ? $request->getQueryParams()['forceFileCached'] : false;

		if(file_exists($this->getFileCached()) && $forceFileCached != true)
		{
			$data = json_decode(file_get_contents($this->getFileCached()));
		}
		else
		{
			$this->setUrl('https://pt.wikipedia.org/wiki/Wikipédia:Efemérides/' . (int) strftime('%d', $this->getDate()) . '_de_' . $month_name);
			$this->setHtml(phpQuery::newDocumentFileHTML($this->getUrl()));

			$data = array(
				'date' => strftime('%Y-%m-%d', $this->getDate()),
				'itens' => array(
					'ephemeris' => array(
						'title' => 'Calendário de ' . strftime('%d', $this->getDate()) . ' ' . $month_name,
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

	/**
	 * @return mixed
	 */
	public function getDay()
	{
		return $this->day;
	}

	/**
	 * @param mixed $day
	 */
	public function setDay($day)
	{
		$this->day = $day;
	}

	/**
	 * @return mixed
	 */
	public function getMonth()
	{
		return $this->month;
	}

	/**
	 * @param mixed $month
	 */
	public function setMonth($month)
	{
		$this->month = ($month >= 1 && $month <= 12) ? $month : date('m');
	}

	/**
	 * @return mixed
	 */
	public function getDirFilesCachedByMonthly()
	{
		return $this->dirFilesCachedByMonthly;
	}

	/**
	 * @param mixed $dirFilesCachedByMonthly
	 */
	public function setDirFilesCachedByMonthly($dirFilesCachedByMonthly)
	{
		$this->dirFilesCachedByMonthly = $dirFilesCachedByMonthly;
	}
}
?>
