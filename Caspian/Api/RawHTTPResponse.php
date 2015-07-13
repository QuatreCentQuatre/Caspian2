<?php

namespace Caspian\Api;

class RawHTTPResponse extends HTTPResponse {

    /**
     * construct
     */
    public function __construct($state)
    {
        parent::__construct($state);
    }

    /**
     * render
     *
     * Render the response (state, message, results) to a JSON format
     *
     * @param   string    The message of the response
     * @param   bool      If we compress the response output
     * @return  void
     *
     */
    public function render($message = '', $compress_output = false)
    {
        header('Content-type: text/html', true, $this->code);
    }
}
