<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\VoucherTemplateHelper;

class Voucher extends Model
{
	protected $fillable = [
		'user_id',
		'description',
		'notes',
		'agent_id',
		'smtp_server_id',
		'activation_date',
		'expiry_date',
		'voucher_type',
		'has_one_code',
		'one_code_quota',
		
		'template',
    'template_path',
		'has_template',
		
		'has_custom_link',
		'custom_link_key',
		
		'entrance_page_type',
		'entrance_page_id',
		'entrance_page_type_after_quota',
		'entrance_page_id_after_quota',
		
		'questionnaire',
		'questionnaire_fields',
		'questionnaire_configs',
    'participant_configs',

//		'thankyou_configs',
//		'sorry_configs',
		
		'goal_type', // fixed, codes, none
		'goal_count',
		
		'action_type_before_goal', // form_voucher, form_custom, custom
		'custom_form_key_before_goal',
		
		'action_type_after_goal', // form_custom, custom, none
		'custom_form_key_after_goal',
		
		'code_fields',
		'code_count',
		
		'participant_count',
		
		'qr_code_composition',
		'qr_code_size',
		
		'sharing_title',
		'sharing_description',
		'sharing_image_id',
		
		'form_sharing_title',
		'form_sharing_description',
		'form_sharing_image_id',
		
		'status',
		'script',
		'email_subject',
		'email_template',
		'email_html_body',
		'email_body',
		'email_cc'
	];
	
	protected static function boot()
	{
		parent::boot();
//
//		static::deleting(function ($voucher) {
//			$voucher->codeInfos()->delete();
//			$voucher->emails()->delete();
//			return true;
//		});
	}
	
	public function codes()
	{
		return $this->hasMany('App\Models\VoucherCode');
	}
	
	public function codeInfos()
	{
		return $this->hasMany('App\Models\VoucherCode');
	}
	
	public function emails()
	{
		return $this->hasMany('App\Models\VoucherEmail');
	}
	
	public function agent()
	{
		return $this->belongsTo('App\Models\Agent');
	}
	
	public function codeConfigs()
	{
		return $this->hasMany('App\Models\VoucherCodeConfig');
	}
	
	public function sharingMedia()
	{
		return $this->belongsTo(Media::class, 'sharing_image_id');
	}
	
	public function formSharingMedia()
	{
		return $this->belongsTo(Media::class, 'form_sharing_image_id');
	}
	
	public function customForms()
	{
		return $this->hasMany(VoucherCustomForm::class);
	}
	
	public function participants() {
		return $this->hasMany(VoucherParticipant::class);
	}

	public function medias() {
	  return $this->belongsToMany(Media::class, 'voucher_medias', 'voucher_id', 'media_id');
  }
	
//	public function getFormConfigsAttribute() {
//		$result = [];
//		if (isset($this->questionnaire_configs) && !empty($this->questionnaire_configs)) {
//			$result = json_decode($this->questionnaire_configs, true);
//		}
//		return $result;
//	}
	
	public function getColumnHeadersAttribute() {
		$result = [];
		if (!empty($this->questionnaire_configs)) {
			$formConfigs = [];
			switch ($this->voucher_type) {
				case 'form':
					$formConfigs = json_decode($this->questionnaire_configs, true);
					break;
				case 'voucher':
					$formConfigs = json_decode($this->participant_configs, true);
					break;
			}
//			$formConfigs = json_decode($this->questionnaire_configs, true);
			if (array_key_exists('inputObjs', $formConfigs)) {
				$inputObjs = $formConfigs['inputObjs'];
				foreach($inputObjs as $i=>$inputObj) {
					$fieldName = 'field'.$i;
					$columnName = $inputObj['name'];
					
					switch ($inputObj['inputType']) {
						case 'simple-text':
						case 'number':
						case 'gender':
						case 'email':
						case 'phone':
						case 'text':
						case 'single-choice':
							$result[] = $columnName;
							break;
							
						case 'multiple-choice':
							$options = $inputObj['options'];
							foreach($options as $option) {
								$result[] = $columnName.'|'.$option;
							}
							break;
							
						case 'name':
							$name = $inputObj['name'];
							$segs = explode(',', $name);
							$hasTwoParts = count($segs)>1;
							if ($hasTwoParts) {
								$result[] = trim($segs[0]); //empty($ inputObj['note1']) ? $inputObj['name'].' (cell #1)' : $inputObj['note1'];
								$result[] = trim($segs[1]); // empty($inputObj['note2']) ? $inputObj['name'].' (cell #2)' : $inputObj['note2'];
							} else {
								$result[] = $name.'[0]';
								$result[] = $name.'[1]';
							}
							break;
					}
				}
			}
		}
		return $result;
	}
	
	public function getInputObjsAttribute() {
		$result = [];
		$formConfigs = [];
		
		switch ($this->voucher_type) {
			case 'form':
				if (!empty($this->questionnaire_configs)) {
					$formConfigs = json_decode($this->questionnaire_configs, true);
				}
				break;
			case 'voucher':
				$formConfigs = json_decode($this->participant_configs, true);
				break;
		}

		if (isset($formConfigs) && array_key_exists('inputObjs', $formConfigs)) {
			$inputObjs = $formConfigs['inputObjs'];
			foreach($inputObjs as $i=>$inputObj) {
				switch ($inputObj['inputType']) {
					case 'simple-text':
					case 'number':
					case 'email':
					case 'gender':
					case 'text':
					case 'single-choice':
					case 'multiple-choice':
					case 'name':
					case 'phone':
						$result[] = $inputObj;
						break;
				}
			}
		}
		return $result;
	}
	
	public function getInputObjFieldsAttribute() {
		$result = [];
		if (!empty($this->questionnaire_configs)) {
			$formConfigs = [];
			switch ($this->voucher_type) {
				case 'form':
					$formConfigs = json_decode($this->questionnaire_configs, true);
					break;
				case 'voucher':
					$formConfigs = json_decode($this->participant_configs, true);
					break;
			}
//			$formConfigs = json_decode($this->questionnaire_configs, true);
			if (array_key_exists('inputObjs', $formConfigs)) {
				$inputObjs = $formConfigs['inputObjs'];
				foreach($inputObjs as $i=>$inputObj) {
					$fieldName = 'field'.$i;
					switch ($inputObj['inputType']) {
						case 'simple-text':
						case 'number':
						case 'email':
						case 'gender':
						case 'text':
						case 'single-choice':
						case 'multiple-choice':
						case 'phone':
							$result[] = $fieldName;
							break;
						case 'name':
							$result[] = $fieldName.'_0';
							$result[] = $fieldName.'_1';
							break;
					}
				}
			}
		}
		return $result;
	}

	public function getTemplateFileAttribute() {
	  return 'v'.$this->id.'.tpl';
  }

//  public function getTemplateFullPath($appFolder) {
//	  $result = null;
//	  if (!is_null($this->template_path) && !empty($this->template_path)) {
//	    $result = storage_path('app/'.$appFolder.'/'.
//        $this->template_path.'/'.
//        'v'.$this->id.'.tpl');
//    }
//	  return $result;
//  }

  public function smtpServers() {
	  return $this->belongsToMany(SmtpServer::class, 'voucher_smtp_servers', 'voucher_id', 'smtp_server_id');
  }
  
  public function getSmtpServer() {
		return SmtpServer::find($this->smtp_server_id);
  }
}
