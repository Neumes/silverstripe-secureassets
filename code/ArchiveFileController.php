<?php

/**
 * Essentially the same as SecureFileController, but it makes an adjustment to how
 * the permissions are handled. If the file is archived it will throw a 404 which
 * is how it would act if the file did not exist.
 */
class ArchiveFileController extends SecureFileController {

	public function handleRequest(SS_HTTPRequest $request, DataModel $model) {
		// Copied from Controller::handleRequest()
		$this->pushCurrent();
		$this->urlParams = $request->allParams();
		$this->request = $request;
		$this->response = new SS_HTTPResponse();
		$this->setDataModel($model);

		$url = array_key_exists('url', $_GET) ? $_GET['url'] : $_SERVER['REQUEST_URI'];

		/* Remove any relative base URL and prefixed slash that get appended to the file path
		   e.g. /mysite/assets/test.txt should become assets/test.txt to match the Filename field on File record */
		$url = Director::makeRelative(ltrim(str_replace(BASE_URL, '', $url), '/'));
		$file = File::find($url);

		if($this->canDownloadFile($file)) {

			// If we're trying to access a resampled image.
			if(preg_match('/_resampled\/[^-]+-/', $url)) {
				
				// File::find() will always return the original image, but we still want to serve the resampled version.
				$file = new Image();
				$file->Filename = $url;
			}
			
			return $this->sendFile($file);
		}
		else {
			
			if($file instanceof File) {
				// Permission failure
				if($file->IsArchived) {
					$this->response = ErrorPage::response_for(404);
				}
				else {
					Security::permissionFailure($this, 'You are not authorised to access this resource. Please log in.');
				}
			}
			else {
				// File doesn't exist
				$this->response = ErrorPage::response_for(404);
			}
		}

		return $this->response;
	}

}

