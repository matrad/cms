<?php

namespace Statamic\View;

use Facades\Statamic\View\Cascade;
use Statamic\Support\Str;
use Statamic\View\Events\ViewRendered;

class View
{
    protected $data = [];
    protected $layout;
    protected $template;
    protected $cascadeContent;

    public static function make($template = null)
    {
        $view = new static;
        $view->template($template);

        return $view;
    }

    public function with($data)
    {
        $this->data = $data;

        return $this;
    }

    public function data()
    {
        return $this->data;
    }

    public function gatherData()
    {
        return array_merge($this->data, $this->cascade());
    }

    public function layout($layout = null)
    {
        if (count(func_get_args()) === 0) {
            return $this->layout;
        }

        $this->layout = $layout;

        return $this;
    }

    public function template($template = null)
    {
        if (! $template) {
            return $this->template;
        }

        $this->template = $template;

        return $this;
    }

    public function render()
    {
        $cascade = $this->gatherData();

        $contents = view($this->template, $cascade);

        // We only want the template-in-a-layout behavior if the template is Antlers.
        $isAntlers = Str::endsWith($contents->getPath(), ['.antlers.html', '.antlers.php']);

        if ($this->layout && $isAntlers) {
            $contents = view($this->layout, array_merge($cascade, [
                'template_content' => $contents->withoutExtractions()->render()
            ]));
        }

        ViewRendered::dispatch($this);

        return $contents->render();
    }

    protected function cascade()
    {
        return Cascade::instance()
            ->withContent($this->cascadeContent)
            ->hydrate()
            ->toArray();
    }

    public function cascadeContent($content = null)
    {
        if (func_num_args() === 0) {
            return $this->cascadeContent;
        }

        $this->cascadeContent = $content;

        return $this;
    }

    public function __toString()
    {
        return $this->render();
    }
}
