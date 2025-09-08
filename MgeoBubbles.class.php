<?php
/*------------------------------------------------------------*/
class MgeoBubbles extends Mcontroller {
	/*------------------------------------------------------------*/
	public function geoBubbles($data, $title, $renderTo) {
		foreach ( $data as $key => $row )
			$data[$key]['z'] = (int)$row['z'];
		$dataJson = json_encode($data);
		$this->Mview->showTpl("geoBubbles.tpl", array(
			'dataJson' => $dataJson,
			'renderTo' => $renderTo,
			'title' => $title,
		));
	}
	/*------------------------------------------------------------*/
}
/*------------------------------------------------------------*/
