<?php
/**
 * @package     PublishPress\Checklists
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Checklists\Core\Requirement;

defined('ABSPATH') or die('No direct script access allowed.');

class Validate_Links extends Base_simple
{

    /**
     * The name of the requirement, in a slug format
     *
     * @var string
     */
    public $name = 'validate_links';

    /**
     * Initialize the language strings for the instance
     *
     * @return void
     */
    public function init_language()
    {
        $this->lang['label']          = __('Validate links format', 'publishpress-checklists');
        $this->lang['label_settings'] = __('Validate links format', 'publishpress-checklists');
    }

    /**
     * Check for links without http(s)
     *
     * @param string $content
     * @param array $invalid_links
     *
     * @return array
     * @since  1.0.1
     */
    private function validate_links_format($content, $invalid_links = array())
    {
        if ($content) {
            //remove any possible email from content so we don't count them as link
            $content = preg_replace("/[^@\s]*@[^@\s]*\.[^@\s]*/", "", $content);

            //match all links inside content
            preg_match_all($re, $str, $links);

            $links = $links[0];

            foreach ($links as $link) {
                //skip if http or https is present in the link build up
                if (strpos($link, 'http://') !== false || strpos($link, 'https://') !== false) {
                    continue;
                }

                //add invalid link to array
                $invalid_links[] = $link;
            }
        }

        return $invalid_links;
    }

    /**
     * Returns the current status of the requirement.
     *
     * @param stdClass $post
     * @param mixed $option_value
     *
     * @return mixed
     */
    public function get_current_status($post, $option_value)
    {
        $count = count($this->validate_links_format($post->post_content));

        return $count == 0;
    }
}
