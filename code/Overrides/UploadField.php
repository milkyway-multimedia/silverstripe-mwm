<?php
/**
 * Milkyway Multimedia
 * UploadField_SelectHandler.php
 *
 * @package milkywaymultimedia.com.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Overrides;

class UploadField extends \UploadField {
	
	/**
	 * @var array
	 */
	private static $allowed_actions = array(
		'upload',
		'attach',
		'handleItem',
		'handleSelect',
		'fileexists'
	);

	/**
	 * @var array
	 */
	private static $url_handlers = array(
		'item/$ID' => 'handleItem',
		'select' => 'handleSelect',
		'$Action!' => '$Action',
	);
	
	/**
	 * Action to handle upload of a single file, extended to work with EditableRow
	 * 
	 * @param SS_HTTPRequest $request
	 * @return SS_HTTPResponse
	 * @return SS_HTTPResponse
	 */
	public function upload(SS_HTTPRequest $request) {
		
		// From regular upload function:
		
		if($this->isDisabled() || $this->isReadonly() || !$this->canUpload()) {
			return $this->httpError(403);
		}
		
		// Protect against CSRF on destructive action
		$token = $this->getForm()->getSecurityToken();
		if(!$token->checkRequest($request)) return $this->httpError(400);
		
		// Get form details
		$name = $this->getName();
		$postVars = $request->postVar($name);
		
		// Save the temporary file into a File object
		$uploadedFiles = $this->extractUploadedFileData($postVars);
		
		// Edits to make UF work with uploads from with an EditableRow:
		if(empty($uploadedFiles) 
				&& strpos($request->param('FieldName'),
						'Milkyway_SS_GridFieldUtils_EditableRow') !== false){
		
/* @TODO: find a way to change the 'name' that gets put in the front-end Uploadfield config, 
 * that should make this whole override unnecessary.
 * 
<input 
 * id="Form_Form_EditForm_ContentBlocks-EditableRow-3_ContentBlocks-Milkyway_SS_GridFieldUtils_EditableRow-3-File" 
 * name="ContentBlocks[Milkyway_SS_GridFieldUtils_EditableRow][3][File][Uploads][]" 
 * ...
 * 
 * Now the post gets this (wrong) structure;
["ContentBlocks"]=>
  array(5) {
    ["name"]=>
    array(1) {
      ["Milkyway_SS_GridFieldUtils_EditableRow"]=>
      array(1) {
        [2]=>
        array(1) {
          ["File"]=>
          array(1) {
            ["Uploads"]=>
            array(1) {
              [0]=>
              string(13) "89509-200.png"
    ...
    ["type"]=>
    array(1) {
      ["Milkyway_SS_GridFieldUtils_EditableRow"]=>
      array(1) {
        [2]=>
        array(1) {
          ["File"]=>
          array(1) {
            ["Uploads"]=>
            array(1) {
              [0]=>
              string(9) "image/png"
      ... (etc)

// relevant request parameters to work with;
["url":protected]=>
  string(140) "admin/pages/edit/EditForm/field/ContentBlocks/editableRow/form/2/field/ContentBlocks[Milkyway_SS_GridFieldUtils_EditableRow][2][File]/upload"
["getVars":protected]=>
  array(1) {
    ["url"]=>
    string(171) "/picknick-festival/site/ontour/admin/pages/edit/EditForm/field/ContentBlocks/editableRow/form/2/field/ContentBlocks[Milkyway_SS_GridFieldUtils_EditableRow][2][File]/upload"
  }
["allParams":protected]=>
  array(4) {
    ["Action"]=>
    string(6) "upload"
    ["ID"]=>
    string(1) "2"
    ["OtherID"]=>
    string(13) "ContentBlocks"
    ["FieldName"]=>
    string(62) "ContentBlocks[Milkyway_SS_GridFieldUtils_EditableRow][2][File]"
  }
*/
			
			$objID = $request->param('ID'); // eg 2
			$relName = $request->param('OtherID'); // eg ContentBlocks
			$fieldName = str_replace(
					"{$relName}[Milkyway_SS_GridFieldUtils_EditableRow][$objID][", '', 
							$request->param('FieldName') );
			$fieldName = str_replace(']', '', $fieldName); // eg File

			$uploadInfo = $request->postVar($relName);
			// $fileInfo['tmp_name']['Milkyway_SS_GridFieldUtils_EditableRow'][2]["File"]['Uploads']
			$amount = count($uploadInfo['tmp_name']['Milkyway_SS_GridFieldUtils_EditableRow'][$objID][$fieldName]['Uploads']);
			
			for($i = 0; $i < $amount; $i++) {
				$tmpFile = array();
				foreach(array('name', 'type', 'tmp_name', 'error', 'size') as $field) {
					$tmpFile[$field] = $uploadInfo[$field]['Milkyway_SS_GridFieldUtils_EditableRow'][$objID][$fieldName]['Uploads'][$i];
				}
				$uploadedFiles[] = $tmpFile;
			}
		}
		
		// Continue with regular functionality:
		
		$firstFile = reset($uploadedFiles);
		$file = $this->saveTemporaryFile($firstFile, $error);
		if(empty($file)) {
			$return = array('error' => $error);
		} else {
			$return = $this->encodeFileAttributes($file);
		}
		
		// Format response with json
		$response = new \SS_HTTPResponse(\Convert::raw2json(array($return)));
		$response->addHeader('Content-Type', 'text/plain');
		if (!empty($return['error'])) $response->setStatusCode(403);
		return $response;
	}
} 