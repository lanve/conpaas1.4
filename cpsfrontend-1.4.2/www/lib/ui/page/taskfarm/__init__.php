<?php
/* Copyright (C) 2010-2013 by Contrail Consortium. */



require_module('ui');
require_module('ui/page');

class TaskFarmPage extends ServicePage {

	public function __construct(Service $service) {
		parent::__construct($service);
		$this->addJS('https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js');
		$this->addJS('https://www.google.com/jsapi');
		$this->addJS('js/jquery.form.js');
		$this->addJS('js/taskfarm.js');
		if ($service->isDemo()) {
			$this->addMessage('This service is running the <b>demo</b> version of the'
				.' TaskFarm service. In this mode the service performs only '
				.' fictional work and it is meant to be used for showing the '
				.' interaction with the system.',
				MessageBox::INFO);
		}
	}

	public function renderActions() {
		$terminateButton = InputButton('terminate')
			->setId('terminate');
		return $terminateButton;
	}

	protected function renderRightMenu() {
		return
			'<div class="rightmenu">'
				.$this->renderStatusSection()
			.'</div>';
	}

	private function getSampleEndpoint() {
		return 'ajax/taskfarm_sample.php?sid='.$this->service->getSID();
	}

	private function renderSampleForm() {
		return
		'<form id="fileForm" action="'.$this->getSampleEndpoint().'">'
		.'<table class="form" cellspacing="0" cellpading="0">'
  			.'<tr>'
  				.'<td class="description">the *.bot file</td>'
  				.'<td class="input">'
				.'<input id="botFile" type="file" name="botFile" />'
  				.'</td>'
  				.'<td class="info">File containing the tasks to be run</td>'
  			.'</tr>'
  			.'<tr>'
  				.'<td class="description">URL</td>'
  				.'<td class="input">'
  					.'<input type="text" name="uriLocation" />'
  				.'</td>'
  				.'<td class="info">'
  					.'mount path for XtremeFS volume (optional)'
  				.'</td>'
  			.'</tr>'
  			.'<tr>'
  				.'<td class="description"></td>'
  				.'<td><input type="button" value="Start sampling" id="startSample" />'
				.'<div class="additional" style="display: inline;">'
					.'<img class="loading invisible" src="images/icon_loading.gif" />'
					.'<i class="positive" style="display: none;">Submitted successfully</i>'
				.'</div>'
  				.'</td>'
  			.'</tr>'
  		.'</table>'
  		.'</form>';
	}

	private function renderSampleSection() {
		return
		'<div class="form-section samplephase">'
		.'<div class="form-header">'
			.'<div class="title">Sampling Phase</div>'
			.'<div class="clear"></div>'
		.'</div>'
		.$this->renderSampleForm()
		.'<div class="clear"></div>'
		.'</div>';
	}

	private function renderExecutionSection() {
		if (!$this->service->hasSamplingResults()) {
			return '';
		}
		return
		'<div class="form-section execphase">'
			.'<div class="form-header">'
				.'<div class="title">Execution phase</div>'
				.'<div class="clear"></div>'
			.'</div>'
			.'<table class="form" id="botexec">'
			.'<tr>'
				.'<td class="description">schedule</td>'
				.'<td class="input">'
					.'<select id="samplings"></select>'
				.'</td>'
				.'<td class="input">'
					.'<div id="executionChart">'
					.'</div>'
				.'</td>'
			.'</tr>'
			.'<tr>'
				.'<td class="description"></td>'
				.'<td class="input">'
					.'<input id="startExec" type="button" value="Start execution"/>'
				.'</td>'
				.'<td>'
					.'<div id="scheduleDetails">'
						.'loading Schedule Details, please wait ...'
					.'</div>'
				.'</td>'
			.'</tr>'
			.'</table>'
		.'</div>';
	}

/*
 *	In demo mode temporarily subtract moneySpent from user_credit to show the credit decrement at work, and also change the display format.
 */
        public function renderUserCredit() {
                $state = $this->service->fetchState();
                $money_spent = 0;
                if (isset($state['result'])) {
                        $result = $state['result'];
                        $mode = $result['mode'];
                        if ($mode == 'DEMO') {
                                return    '<div class="runusercredit" id="run_user_credit">'
                                        . ($this->user_credit)
                                        . '</div>'
                                        . '<div class="demousercredit" id="demo_user_credit">'
                                        . ($this->user_credit - $result['moneySpent'])
                                        . '</div>'
                                        . '<div class="demousercreditexplanation" id="demo_user_credit_explanation">'
                                        . '(demo credits)'
                                        . '</div>';
                                
                        }
                }
                return $this->user_credit;
        }

	private function renderStatusSection() {
		$state = $this->service->fetchState();
		if (!isset($state['result'])) {
			dlog('Cannot read detailed state');
			return '';
		}
		$state = $state['result'];
		return
		'<div id="taskfarm-status">'
			.StatNumber($state['moneySpent'], 'money spent', '$')
				->setId('moneySpent')
			.StatNumber($state['noTotalTasks'], 'of')
				->setId('totalTasks')
			.StatNumber($state['noCompletedTasks'], 'completed tasks')
				->setId('completedTasks')
			.'<div class="clear"></div>'
		.'</div>';
	}

	private function renderProgressBar() {
		return
		'<div id="progressbar" class="progressbar">'
			.'<div class="progresswrapper">'
				.'<div class="progress"></div>'
			.'</div>'
			.'<div class="percent">'
				.'0%'
			.'</div>'
		.'</div>';
	}

	public function renderDemoQuestion() {
		$intro = 'The TaskFarm service has the option of running in <b>demo</b> '
		.'mode besides the regular <b>real</b> mode. The <b>demo</b> mode '
		.'encapsulates a mocked service that is showing how the system works '
		.'and how you can interact with it, so it might a good option when '
		.'trying the system for the first time.';
		$question = 'Do you want to run the system in <b>real</b> mode?';
		return
		'<div class="demoform">'
			.'<p>'.$intro.'</p>'
			.'<div class="question">'
				.$question
				.InputButton('Yes')->setId('enableReal')
				.InputButton('No, run demo')->setId('enableDemo')
				.'<span class="hint">'
					.'once you choose a running mode, you cannot switch it again'
				.'</span>'
			.'</div>'
		.'</div>';
	}

	public function renderContent() {
		if ($this->service->isDemoNotSet()) {
			return $this->renderDemoQuestion();
		}
		return
			$this->renderProgressBar()
			.$this->renderSampleSection()
			.$this->renderExecutionSection();
	}
}

?>
