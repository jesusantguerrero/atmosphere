<?php

namespace Freesgen\Atmosphere\Http;
use Illuminate\Http\Request as BaseRequest;

class InertiaRequest extends BaseRequest {

    public function postData() {
        $postData = $this->post();

        $postData['user_id'] = $this->user()->id;
        $postData['team_id'] = $this->user()->current_team_id;
        return $postData;
    }
}
