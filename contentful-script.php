<?php
/**
 * PHP script for handling contentful API tasks
 * @author Kaleb Woldearegay <kaleb@gullele.com>
 * =================  PLEASE READ =================
 * This script is to be used by anybody whoever wants it. It would be cool if you keep the author name
 * but you can total remove and use it as if you created it. 
 * You will use the whole or part of this script on your own RISK. I won't be responsible for anykind of 
 * loss or unexpected dataloss or update or whatever related to data breakage, data loss, financial or 
 * moral or anything that can happen using this script.
 */
class ContentfulStuff
{
	//access token for reading from the source
	private $source_access_token;
	//OAuth based access token that has special priviledge for writing/updating to destination space
	private $destination_auth_token;
	//access token for reading from the destionation
	private $destination_access_token;
	//source space id
	private $source_space_id;
	//destination space id
	private $destination_space_id;
	private $read_api = 'cdn.contentful.com';
	private $write_api = 'api.contentful.com';

	public function setSourceAccessToken($access_token)
	{
		$this->source_access_token = $access_token;
	}

	public function setDestinationAccessToken($access_token)
	{
		$this->destination_access_token = $access_token;
	}

	public function setDestinationAuthToken($access_token)
	{
		$this->destination_auth_token = $access_token;
	}

	public function setSourceSpaceId($space_id)
	{
		$this->source_space_id = $space_id;
	}

	public function setDestionationSpaceId($space_id)
	{
		$this->destination_space_id = $space_id;
	}

	/**
	 * Handling simple curl - 
	 * @todo use guzzle
	 */
	private function sendRequest($url, $method='GET', $fields = "", $header=array())
	{	
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    if ($method == 'POST') { //check for http verb here..
	    	curl_setopt($ch, CURLOPT_POST, TRUE);
	    }
	    curl_setopt($ch, CURLOPT_HEADER, FALSE);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
	    
	    if (!empty($header)) {
	    	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	    }
	  
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	    $output = curl_exec($ch);
	    curl_close($ch);
	 
	    return $output;
	}

	/**
	 * Get entries from the given space
	 */
	public function getEntities($space_id, $access_token)
	{
		$url = $this->read_api."/spaces/{$space_id}/entries?access_token={$access_token}";
		$entries = $this->sendRequest($url);
		return $entries;
	}

	public function getAllContentTypes($space_id, $access_token)
	{
		$url = $this->read_api."/spaces/{$space_id}/content_types?access_token={$access_token}";
		$content_types = $this->sendRequest($url);
		return $content_types;
	}

	public function getContentType($space_id, $content_type_id, $access_token)
	{
		$url = $this->read_api."/spaces/{$space_id}/content_types/{$content_type_id}?access_token={$access_token}";
		return $this->sendRequest($url);
	}

	public function getAllEntries($space_id, $access_token)
	{
		$url = $this->read_api."/spaces/{$space_id}/entries?access_token={$access_token}";
		return $this->sendRequest($url);
	}

	public function getEntry($space_id, $entry_id, $read_access_token)
	{
		$url = $this->read_api."/spaces/{$space_id}/entries/{$entry_id}?access_token={$read_access_token}";
		return $this->sendRequest($url);
	}

	public function publishContentType($space_id, $content_type_id, $version, $access_token)
	{
		$url = $this->write_api."/spaces/{$space_id}/content_types/{$content_type_id}/published";
		$header = array(
	  		"Authorization: Bearer {$access_token}",
	  		"Content-Type: application/vnd.contentful.management.v1+json",
	  		"X-Contentful-Version: {$version}"
		);
		return $this->sendRequest($url, 'PUT', "", $header);	
	}

	public function createSpace($access_token, $name)
	{
		$url = $this->write_api."/spaces";
		return $this->sendRequest($url, 'POST', json_encode(['name'=>$name]));	
	}

	public function createEntry($space_id, $access_token, $content_type_id, $data)
	{
		$url = $this->write_api."/spaces/{$space_id}/entries";
		$header = array(
	  		"Authorization: Bearer {$access_token}",
	  		"Content-Type: application/vnd.contentful.management.v1+json",
	  		"X-Contentful-Content-Type: {$content_type_id}"
		);
		return $this->sendRequest($url, 'POST', json_encode($data), $header);	
	}

	public function publishEntry($space_id, $access_token, $entry_id, $version)
	{
		$url = $this->write_api."/spaces/{$space_id}/entries/{$entry_id}/published";
		$header = array(
	  		"Authorization: Bearer {$access_token}",
	  		"Content-Type: application/vnd.contentful.management.v1+json",
	  		"X-Contentful-Version: {$version}"
		);

		return $this->sendRequest($url, 'PUT', "", $header);
	}

	public function createContentType($space_id, $access_token, $data)
	{
		$url = $this->write_api."/spaces/{$space_id}/content_types";
		$header = array(
	  		"Authorization: Bearer {$access_token}",
	  		"Content-Type: application/vnd.contentful.management.v1+json"
		);
		return $this->sendRequest($url, 'POST', json_encode($data), $header);	
	}

	/**
	 * Get all the content types to from the source to the new space
	 */
	public function cloneContentType($read_space_id, $read_access_token, $write_space_id, $write_auth_token)
	{
		//get all the contenttypes from the source
		$sources = json_decode(getAllContentTypes($read_space_id, $read_access_token));

		foreach ($sources->items as $item) {
			$content = new stdClass;
			$content->name = $item->name;
			$content->fields = $item->fields;
			$new_content = json_decode(createContentType($write_space_id, $write_auth_token, $content));
			if ($new_content) {
				echo "Content {$item->name} Created \n";
				echo "Publishing the content....\n";
				$published = publishContentType($write_space_id, $new_content->sys->id, 1);
				if ($published) {
					echo "Content published, adding entry....\n";
				}
			}		
		}
	}

	/**
	 * After creating content types, you might want to clone Entities as well.
	 */
	public function cloneEntity($read_space_id, $read_access_token, $write_space_id, $write_auth_token, $write_access_token)
	{
		//get all the content types
		$all_contents = json_decode(getAllContentTypes($write_space_id, $write_access_token));
		$content_matrix = [];
		foreach ($all_contents->items as $content) {
			$content_matrix[$content->name] = $content->sys->id;
		}

		//get all the contenttypes from the source
		echo "Pulling entities...\n";
		$entries = json_decode(getAllEntries($read_space_id, $read_access_token));
		foreach ($entries->items as $item) {
			$new_entry = (array)$item->fields;
			foreach ($new_entry as $key=>$value) {
				$new_entry[$key] = ['en-US'=>$value]; //check if you have different/dynamic language code
			}

			$entry['fields'] = $new_entry;
			$content_type_id = $item->sys->contentType->sys->id;
			//grab the contentType for getting the name
			$content_type = json_decode(getContentType($read_space_id, $content_type_id, $read_access_token));
			echo "Entry for type content {$content_type->name} prepared...\n";
			$content_type_id = $content_matrix[$content_type->name];
			$new_entry = json_decode(createEntry($write_space_id, $write_auth_token, $content_type_id, $entry));
			if (!empty($new_entry->sys)) {
				echo "Publishing...\n";
				$published = publishEntry($write_space_id, $write_access_token, $new_entry->sys->id, 1);
				if (strpos($published, 'publishedVersion')) { //this is important
					echo "PUBLISHED!!\n";
				}
			}
		}
	}
}

$contentful = new ContentfulStuff();
//space cloning can be performed first by cloning content type and then cloning the entries