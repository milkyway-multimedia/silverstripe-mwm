<?php namespace Milkyway\SS\FlashMessage;

use Controller as Original;

class Controller extends Original
{
    private static $allowed_actions = [
        'refresh',
    ];

    private static $url_handlers = [
        '$Area!' => 'refresh',
    ];

    /**
     * Grab available notifications
     *
     * @param $request
     * @return array|\SS_HTTPResponse|void
     * @throws \SS_HTTPResponse_Exception
     */
    public function refresh($request) {
        // Only available via AJAX
        if(!$request->isAjax()) {
            return $this->httpError(404);
        }

        $area = $request->param('Area');
        singleton('require')->clear();

        // If no area specified, do nothing
        if(!$area) {
            return [];
        }

        // Add additional messages via an extension (if you want to call an API etc.)
        $this->extend('onRefresh', $area, $request);

        $response = $this->getResponse();
        $response->addHeader('Content-type', 'application/json');
        $response->setBody(json_encode(singleton('message')->$area()->get()));
        return $response;
    }

    public function Link($area = '') {
        return $this->join_links('notifications', $area);
    }
}