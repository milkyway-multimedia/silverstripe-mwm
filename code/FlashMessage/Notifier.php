<?php namespace Milkyway\SS\FlashMessage;

use Session;
use Controller;

class Notifier
{
    /* @var string Key for storage which holds these messages */
    protected $id = 'messages';

    /* @var string Default area to display message */
    protected $defaultArea = 'cms';

    /* @var array Support message levels */
    protected $levels = [
        'info',
        'success',
        'error',
        'warning',
//        'modal',
//        'note',
    ];

    /* @var array Links to load before notifications are inserted (for messages from APIs) */
    protected $before = [];

    /* @var array Areas with JS already included */
    protected $jsIncluded = [];

    /** bool Only allow unique messages */
    protected $unique = true;

    /* @var string Current area to display message */
    protected $workingArea = '';

    public function __construct()
    {
        $this->defaultArea = singleton('env')->get('messages.default_area', $this->defaultArea);
    }

    /**
     * Add a message to the notifier
     *
     * @param $content
     * @param string $level
     * @param int $timeout
     * @param int $priority
     * @param bool $dismissable
     * @param string $area
     * @return $this
     */
    public function add($content, $level = 'info', $timeout = 0, $priority = 0, $dismissable = false, $area = '')
    {
        $area = $area ?: is_array($content) && isset($content['area']) ? $content['area'] : $this->workingArea ?: $this->defaultArea;

        if (!$this->canView($area)) {
            return $this;
        }

        $this->style($area);

        $messages = (array)Session::get($this->id . '.' . $area);

        array_unshift($messages, is_array($content) ? $content : [
            'content'     => $content,
            'level'       => $level,
            'timeout'     => $timeout,
            'priority'    => $priority,
            'dismissable' => $dismissable,
        ]);

        Session::set($this->id . '.' . $area, $messages);

        $this->workingArea = '';

        return $this;
    }

    /**
     * Remove a message from the notifier
     *
     * @param string $content
     * @param string $level
     * @param string $area
     * @return $this
     */
    public function remove($content = null, $level = '', $area = '')
    {
        $this->clear(is_array($content) ? $content : [
            'content' => $content,
            'level'   => $level,
            'area'    => $area ?: $this->workingArea ?: $this->defaultArea,
        ]);

        $this->workingArea = '';

        return $this;
    }

    /**
     * Clear a message that matches a set of params
     *
     * @param array $params
     * @return $this
     */
    public function clear($params = [])
    {
        if (isset($params['level']) || isset($params['content'])) {
            $level = isset($params['level']) ? $params['level'] : '';
            $content = isset($params['content']) ? $params['content'] : '';
            $area = isset($params['area']) ? $params['area'] : '';
            $messages = $area ? (array)Session::get($this->id . '.' . $area) : (array)Session::get($this->id);

            $adjust = function ($messages) use ($content, $level) {
                $new = [];

                foreach ($messages as $message) {
                    if ($content && $level && $message['content'] != $content && $message['level'] != $level) {
                        $new[] = $message;
                    } else {
                        if ($content && !$level && $message['content'] != $content) {
                            $new[] = $message;
                        } else {
                            if (!$content && $level && $message['level'] != $level) {
                                $new[] = $message;
                            }
                        }
                    }
                }

                return $new;
            };

            if ($area) {
                Session::set($this->id . '.' . $area, $adjust($messages));
            } else {
                foreach ($messages as $area => $areaMessages) {
                    Session::set($this->id . '.' . $area, $adjust($areaMessages));
                }
            }
        } else {
            if (isset($params['area'])) {
                Session::clear($this->id . '.' . $params['area']);
            }
        }

        return $this;
    }

    /**
     * Get messages for an area
     * @param string $area
     * @return array
     */
    public function get($area = '')
    {
        $area = $area ?: $this->workingArea ?: $this->defaultArea;
        $messages = (array)Session::get($this->id . '.' . $area);
        Session::clear($this->id . '.' . $area);
        $this->workingArea = '';

        if ($this->unique) {
            $content = [];
            $messages = array_filter($messages, function ($message) use (&$content) {
                if (in_array($message['content'], $content)) {
                    return false;
                } else {
                    array_push($content, $message['content']);

                    return true;
                }
            });
        }

        // Sorting is handled in JS
//        return usort($messages, function($a, $b) {
//            return $a['priority'] - $b['priority'];
//        });

        return $messages;
    }

    /**
     * Add a link to execute before messages are added
     * @param $link
     * @param string $area
     */
    public function before($link, $area = '')
    {
        $area = $area ?: $this->workingArea ?: '';

        if (isset($this->before[$link])) {
            $this->before[$link] = array_merge($this->before[$link], explode(',', $area));
        } else {
            $this->before[$link] = explode(',', $area);
        }

        $this->workingArea = '';
    }

    public function removeBefore($link, $area = '')
    {
        if (!isset($this->before[$link])) {
            return;
        }

        $area = $area ?: $this->workingArea ?: '';

        $this->before[$link] = array_diff($this->before[$link], explode(',', $area));

        $this->workingArea = '';
    }

    /**
     * Handle some fancy stuff for getting messages
     *
     * @param $name
     * @param $arguments
     * @return $this|mixed
     */
    public function __call($name, $arguments)
    {
        if (in_array($name, $this->levels)) {
            return call_user_func_array([$this, 'message'], $arguments);
        }

        $this->workingArea = $name;

        return $this;
    }

    protected function style($area)
    {
        if (in_array($area,
                $this->jsIncluded) || singleton('env')->get('messages.exclude_js') || singleton('env')->get('messages.exclude_' . $area . '_js')
        ) {
            return;
        }

        $params = [
            'Before' => '',
            'Link'   => singleton('Milkyway\SS\FlashMessage\Controller')->Link($area),
        ];

        foreach ($this->before as $link => $areas) {
            if (empty($areas) || in_array($area, $areas)) {
                $params['Before'] .= $link . ',';
            }
        }

        $params['Before'] = trim($params['Before'], ',');

        singleton('require')->javascriptTemplate(SS_MWM_DIR . '/js/mwm.flash-messages.link.js', $params,
            SS_MWM_DIR . '/js/mwm.flash-messages.link.js:' . $area);

        $this->jsIncluded[] = $area;

        if (!singleton('env')->get('messages.exclude_lib_js')) {
            singleton('require')->javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
            singleton('require')->javascript(SS_MWM_DIR . '/js/mwm.flash-messages.js');
        }

        if (!singleton('env')->get('messages.exclude_lib_css')) {
            singleton('require')->css(SS_MWM_DIR . '/css/mwm.flash-messages.css');
        }
    }

    /**
     * Check whether the area is limited by controller type
     * @param $area
     * @return bool
     */
    protected function canView($area)
    {
        $mapping = (array)singleton('env')->get('messages.mapping');

        if (isset($mapping[$area])) {
            $allowed = array_filter((array)$mapping[$area]);

            foreach ($allowed as $controller) {
                if (is_a(Controller::curr(), $controller)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }
}
