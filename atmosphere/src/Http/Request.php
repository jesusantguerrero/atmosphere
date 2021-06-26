<?php

namespace Atmosphere\Http;
use Illuminate\Http\Request as BaseRequest;

class Request extends BaseRequest {

    public function postData() {
        $postData = $this->post();

        $postData['user_id'] = $this->user()->id;
        $postData['team_id'] = $this->user()->current_team_id;
        return $postData;
    }
}
