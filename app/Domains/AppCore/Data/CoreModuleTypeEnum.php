<?php

namespace App\Domains\AppCore\Data;

enum CoreModuleTypeEnum: string {
  case Finance = 'finance';
  case Meals = 'meals';
  case Housing = 'housing';
  case Profiles = 'profiles';
}
