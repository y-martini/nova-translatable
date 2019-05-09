<?php

namespace YesWeDev\Nova\Translatable;

use Illuminate\Contracts\Validation\Rule;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Field;

class Translatable extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'translatable';

    /**
     * Create a new field.
     *
     * @param  string  $name
     * @param  string|null  $attribute
     * @param  mixed|null  $resolveCallback
     * @return void
     */
    public function __construct($name, $attribute = null, $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $locales = array_map(function ($value) {
            return __($value);
        }, config('translatable.locales'));

        $this->withMeta([
            'locales' => $locales,
            'indexLocale' => app()->getLocale(),
            'tinyApi' => config('nova.tiny_mce.api_key'),
            'tinyOptions' => config('nova.tiny_mce.options')
        ]);
    }

    /**
     * Resolve the given attribute from the given resource.
     *
     * @param  mixed  $resource
     * @param  string  $attribute
     * @return mixed
     */
    protected function resolveAttribute($resource, $attribute)
    {
        if ( class_exists('\Spatie\Translatable\TranslatableServiceProvider') && method_exists($resource, 'getTranslations') ) {
            $results = $resource->getTranslations($attribute);
        } elseif ( class_exists('\Dimsav\Translatable\TranslatableServiceProvider') && method_exists($resource, 'translations') ) {
            $results = $resource->translations->pluck($attribute, config('translatable.locale_key'));
        } else {
            $results = data_get($resource, $attribute);
        }
        return $results;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $requestAttribute
     * @param  object  $model
     * @param  string  $attribute
     * @return void
     */
    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
        if ( class_exists('\Spatie\Translatable\TranslatableServiceProvider') ) {
            parent::fillAttributeFromRequest($request, $requestAttribute, $model, $attribute);
        } elseif ( class_exists('\Dimsav\Translatable\TranslatableServiceProvider') && method_exists($resource, 'translateOrNew') ) {
            if ( is_array($request[$requestAttribute]) ) {
                foreach ( $request[$requestAttribute] as $lang => $value ) {
                    $model->translateOrNew($lang)->{$attribute} = $value;
                }
            }
        } else {
            parent::fillAttributeFromRequest($request, $requestAttribute, $model, $attribute);
        }
    }

    /**
     * Set the locales to display / edit.
     *
     * @param  array  $locales
     * @return $this
     */
    public function locales(array $locales)
    {
        return $this->withMeta(['locales' => $locales]);
    }

    /**
     * Set the locale to display on index.
     *
     * @param  string $locale
     * @return $this
     */
    public function indexLocale($locale)
    {
        return $this->withMeta(['indexLocale' => $locale]);
    }

    /**
     * Set the input field to a single line text field.
     */
    public function singleLine()
    {
        return $this->withMeta(['singleLine' => true]);
    }

    /**
     * Use Trix Editor.
     */
    public function trix()
    {
        return $this->withMeta(['trix' => true]);
    }

    /**
     * Use TinyMCE Editor.
     */
    public function tiny()
    {
        return $this->withMeta(['tiny' => true]);
    }

    /**
     * Allow to pass any existing TinyMCE option to the editor.
     * Consult the TinyMCE documentation [https://github.com/tinymce/tinymce-vue]
     * to view the list of all the available options.
     *
     * @param  array $options
     * @return self
     */
    public function tinyOptions(array $options)
    {
        $currentOptions = $this->meta['tinyOptions'];

        return $this->withMeta(
            [
                'tinyOptions' => array_merge($currentOptions, $options)
            ]
        );
    }

    /**
     * Set the validation rules for the field.
     *
     * @param  callable|array|string $rules
     * @return $this
     */
    public function rules($rules)
    {
      parent::rules($this->transformRules(($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules));
      return $this;
    }

    /**
     * Set the creation validation rules for the field.
     *
     * @param  callable|array|string  $rules
     * @return $this
     */
    public function creationRules($rules)
    {
      parent::creationRules($this->transformRules(($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules));
      return $this;
    }

    /**
     * Set the creation validation rules for the field.
     *
     * @param  callable|array|string $rules
     * @return $this
     */
    public function updateRules($rules)
    {
      parent::updateRules($this->transformRules(($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules));
      return $this;
    }

    private function transformRules($rules){
      return [function($attribute, $value, $fail) use ($rules) {
        $r = [];
        foreach (array_keys(config('translatable.locales')) as $locale){
            $r[$locale] = [];
            foreach ($rules as $rule)
                $r[$locale] = array_merge($r[$locale], is_string($rule) ? explode('|', $rule) : [$rule]);
        }

        $validator = validator($value, $r);
        if ($validator->fails()) {
          foreach ($validator->errors()->all() as $message) {
            return $fail($message);
          }
        }

        return true;
      }];
    }

    /**
     * Display the field as raw HTML.
     */
    public function asHtml()
    {
        return $this->withMeta(['asHtml' => true]);
    }

    /**
     * Truncate on Detail Page.
     */
    public function truncate()
    {
        return $this->withMeta(['truncate' => true]);
    }
}
