<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;

class IngredientApiController extends BaseController
{
    public function __construct()
    {
        $this->model = new Product();
        $this->searchable = ['name'];
        $this->validationRules = [];
    }


    public function addLabel ($id)
    {
        $postData = request()->post();
        $product = $this->model->find($id);
        if ($postData['label_id'] !== 'new') {
            $label = $product->labels()->find($postData['label_id']);
        } else {
            $label = $product->labels()->create([
                "user_id" => auth()->user()->id,
                "team_id" => auth()->user()->current_team_id,
                "name" => $postData['name'],
                "label" => $postData['name'],
                "color" => $postData['color'],
            ]);
        }

        return response()->json(['label' => $label]);
    }
}
