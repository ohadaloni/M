<?php
/*------------------------------------------------------------*/
class MpieCharts extends Mcontroller {
	/*------------------------------------------------------------*/
	public function pieChart($values, $title, $renderTo) {
		$chartData = $this->chartData($values, $title);
		$chartJson = json_encode($chartData);
		$this->Mview->showTpl("pieChart.tpl", array(
			'chartJson' => $chartJson,
			'renderTo' => $renderTo,
		));
	}
	/*------------------------------*/
	private function chartData($values, $title) {
		$sum = array_sum($values);
		$percentages = array();
		foreach ( $values as $origName => $value ) {
			$name = "$origName ($value)";
			$percentages[] = array($name, $value*100.0/$sum);
		}
		$chart = array(
			'chart' => array(
				'plotBackgroundColor' => null,
				'plotBorderWidth' => null,
				'plotShadow' => false,
			),
			'title' => array(
				'text' => $title,
			),
			'tooltip' => array(
				'pointFormat' => '{series.name}: <b>{point.percentage:.1f}%</b>',
			),
			'plotOptions' => array(
				'pie' => array(
					'allowPointSelect' => true,
					'cursor' => 'pointer',
					'dataLabels' => array(
						'enabled' => true,
						'format' => '<b>{point.name}</b>: {point.percentage:.1f} %',
						'style' => array(
							'color' => "(Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'",
						),
					),
				),
			),
			'series' => array(array(
				'type' => 'pie',
				'name' => $title,
				'data' => $percentages,
			)),
				
		);
		return($chart);
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
