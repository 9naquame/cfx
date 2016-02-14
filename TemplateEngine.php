<?php
/*
 * WYF Framework
 * Copyright (c) 2011 James Ekow Abaka Ainooson
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * A simple wrapper class for smarty. This class provides the boilerplate code
 * for initialising the template engine for use in the WYF framework.
 */
class TemplateEngine extends Smarty
{
    const PROTO_FILE = 'file';
    const PROTO_STRING = 'string';
    
    /**
     * Sets up the smarty engine
     */
    public function __construct()
    {
        parent::__construct();
        $this->template_dir = SOFTWARE_HOME . 'app/themes/' . Application::$config['theme'] . '/templates';
        $this->compile_dir = SOFTWARE_HOME . 'app/cache/template_compile';
        $this->config_dir = SOFTWARE_HOME . 'config/template/';
        $this->cache_dir = SOFTWARE_HOME . 'app/cache/template';
        $this->caching  = false;
        $this->assign('host',$_SERVER["HTTP_HOST"]);
    }
    
    /**
     * Renders a given smarty template and returns the output as a string.
     * The template passed to this function could either be a string or a file.
     * The type of template is specified through the $proto parameter. If a
     * protocol is not specified the render function reads in a file.
     * 
     * @param string $template Template string or path to template
     * @param array $data The data to render the template with.
     * @param string $proto The type of template. 'file' for files and 'string' for strings.
     *
     * @return string
     */
    public static function render($template, $data, $proto = 'file')
    {
        $t = new TemplateEngine();
        $t->assign($data);
        return @$t->fetch("{$proto}:" . ($proto === 'file' ? "/" . getcwd() . "/" : '') . "$template");
    }
    
    /**
     * Render a string template and output html.
     *
     * @param string $template The smarty template string
     * @return string
     */
    public static function renderString($template, $data)
    {
        return self::render($template, $data, 'string');
    }
}
