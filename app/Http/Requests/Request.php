<?php namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class Request extends FormRequest
{
	/**
	 * Validation rules that apply to the request.
	 * @var array
	 */
	protected $rules = [];

	/**
	 * Human friendly names of the request fields under validation.
	 * @var array
	 */
	protected $labels = [];

	/**
	 * Class constructor.
	 *
	 * @param  array
	 * @return void
	 */
	public function __construct($rules = [])
	{
		if($rules)
			list($this->labels, $this->rules) = self::parseRulesAndLabels($rules);
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules()
	{
		// Set the id to be excluded in the 'unique' rules
		static $replacement = null;
		if(is_null($replacement))
		{
			$id = $this->getKey();
			$replacement = (is_integer($id)) ? ",$id" : '';

			foreach($this->rules as $field => $rules)
				$this->rules[$field] = str_replace('{excludeCurrentId}', $replacement, $rules); //TODO refactor to support array of rules
		}

		return $this->rules;
	}

	/**
	 * Get the human friendly names of the request fields under validation.
	 *
	 * @return object
	 */
	public function labels()
	{
		return (object) $this->labels;
	}

	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize()
	{
		//NOTE use $this->user() to access current user instance

		return true;
	}

	/**
	 * Get the resource primary key (id) from request URL.
	 *
	 * NOTE: It can also be retrieved from the route parameter name using
	 * $this->route('paramName') method, but that involves knowing the name
	 * of the parameter beforehand. i.e:
	 *
	 * For the route
	 *     Route::post('comment/{comment}')
	 *
	 * the value of $comment can be retrieved by
	 *
	 *     $this->route('comment');
	 *
	 * @return integer|null
	 */
	public function getKey()
	{
		$segments = $this->segments();
		$last = array_pop($segments);

		return (ctype_digit($last)) ? intval($last) : null;
	}

	/**
	 * Parse validation rules in compact format.
	 *
	 * Converts:
	 *
	 * [
	 *   'field1' => ['Label1', 'A|B'],
	 *   'field2' => ['Label2', ['C','D']],
	 * ];
	 *
	 * To:
	 *
	 * [
	 *   'labels' => [
	 *     'field1' => 'Label1',
	 *     'field2' => 'Label2',
	 *   ],
	 *   'rules' => [
	 *     'field1' => ['A', 'B'],
	 *     'field2' => ['C', 'D'],
	 *  ]
	 *];
	 *
	 * @param  array
	 * @return array
	 */
	public static function parseRulesAndLabels(array $originalRules)
	{
		$parsed = ['labels' => [], 'rules' => []];

		foreach($originalRules as $field => $labelAndRules)
		{
			list($label, $rules) = $labelAndRules;

			// Add label
			$parsed['labels'][$field] = $label;

			// Sometimes a column has no rules
			if(is_null($rules))
				continue;

			// Convert rules to array
			if( ! is_array($rules))
				$rules = explode('|', $rules);

			// Convert rules to associative array
			foreach($rules as $key => $value)
			{
				$new_key = explode(':', $value);
				$rules[$new_key[0]] = $value;
				unset($rules[$key]);
			}

			// Add rules
			$parsed['rules'][$field] = $rules;
		}

		return array_values($parsed);
	}

	/**
	 * Handle a failed validation attempt.
	 *
	 * @param  \Illuminate\Validation\Validator  $validator
	 * @return mixed
	 */
	protected function failedValidation(Validator $validator)
	{
		// Set custom error attibute names
		$validator->setAttributeNames($this->labels); //TODO not working. According to dd($validator) the instance has the right attibute names but they are not used!

		return parent::failedValidation($validator);
	}
}
