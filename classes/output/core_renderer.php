<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Theme Pimenko renderer file.
 *
 * @package    theme_pimenko
 * @copyright  Pimenko 2020
 * @author     Sylvain Revenu - Pimenko 2020 <contact@pimenko.com> <pimenko.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_pimenko\output;

use core_auth\output\login;
use stdClass;
use theme_config;
use core_course_category;
use context_course;
use custom_menu;
use html_writer;
use completion_info;
use context_system;
use moodle_url;
use theme_pimenko\output\core\navigation\primary as primary;

/**
 * Class core_renderer extended
 *
 * @package    theme_pimenko
 * @copyright  Pimenko 2020
 * @author     Sylvain Revenu - Pimenko 2020 <contact@pimenko.com> <pimenko.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class core_renderer extends \theme_boost\output\core_renderer {
    private $themeconfig;

    /**
     * Returns template of login page.
     *
     * @param $output
     *
     * @return string
     */
    public static function renderer_contactus($output): string {

        return $output->render_from_template(
            'theme_pimenko/contactus', []
        );
    }

    /** Render a pix using different system of moodle */
    public function render_custom_pix($output, string $pixstring): string {
        // Define some needed var for ur template.
        $template = new stdClass();
        $template->pixstring = $pixstring;
        return $output->render_from_template(
            'theme_pimenko/pix', $template
        );
    }

    /**
     *
     * Display activity navigation.
     *
     * @return bool
     */
    public function show_activity_navigation(): bool {
        $themeconfig = theme_config::load('pimenko');
        $showactivitynav = false;
        if ($themeconfig->settings->showactivitynavigation) {
            $showactivitynav = true;
        }
        return $showactivitynav;
    }

    /**
     * Returns template of login page.
     *
     * @param $output
     *
     * @return string
     */
    public function render_login_page($output): string {
        global $SITE;

        $extraclasses = [];

        // Define some needed var for ur template.
        $template = new stdClass();
        $template->sitename = format_string(
            $SITE->shortname, true, [
                'context' => context_course::instance(SITEID),
                "escape" => false
            ]
        );
        $template->bodyattributes = $output->body_attributes($extraclasses);

        // Output content.
        $template->output = $output;

        // Main login content.
        $template->maincontent = $output->main_content();

        $renderer = $this->page->get_renderer('core');

        $primary = new primary($this->page);
        $primarymenu = $primary->export_for_template($renderer);

        $template->primarymoremenu = $primarymenu['moremenu'];

        // Hide site name option.
        $theme = theme_config::load('pimenko');
        $template->hidesitename = $theme->settings->hidesitename;
        $template->langmenu = $primarymenu['lang'];

        $template->mobileprimarynav = $primarymenu['mobileprimarynav'];

        return $output->render_from_template(
            'theme_pimenko/login', $template
        );
    }

    /**
     * @return string
     */
    public function sitelogo(): string {
        $sitelogo = '';
        if (!empty($this->page->theme->settings->sitelogo)) {
            if (empty($this->themeconfig)) {
                $this->themeconfig = $theme = theme_config::load('pimenko');
            }
            $sitelogo = $this->themeconfig->setting_file_url(
                'sitelogo', 'sitelogo'
            );
        }
        return $sitelogo;
    }

    /**
     * Return the picture set in theme option.
     *
     * @return string
     */
    public function navbarpicture(): string {
        $navbarpicture = '';
        if (!empty($this->page->theme->settings->navbarpicture)) {
            if (empty($this->themeconfig)) {
                $this->themeconfig = $theme = theme_config::load('pimenko');
            }
            $navbarpicture = $this->themeconfig->setting_file_url(
                'navbarpicture', 'navbarpicture'
            );
        }
        return $navbarpicture;
    }

    /**
     * Render footer
     *
     * @return string footer template
     */
    public function footer_custom_content(): string {
        $theme = theme_config::load('pimenko');

        $template = new stdClass();

        $template->columns = [];

        for ($i = 1; $i <= 4; $i++) {
            $heading = "footerheading{$i}";
            $text = "footertext{$i}";
            if (isset($theme->settings->$text) && !empty($theme->settings->$text)) {
                $space = [
                    '/ /',
                    "/\s/",
                    "/&nbsp;/",
                    "/\t/",
                    "/\n/",
                    "/\r/",
                    "/<p>/",
                    "/<\/p>/"
                ];
                $textwithoutspace = preg_replace(
                    $space, '', $theme->settings->$text
                );
                if (!empty($textwithoutspace)) {
                    $column = new stdClass();
                    $column->text = format_text($theme->settings->$text, FORMAT_HTML);
                    $column->classtext = $text;
                    $column->list = [];
                    $menu = new custom_menu(
                        $column->text, current_language()
                    );
                    foreach ($menu->get_children() as $item) {
                        $listitem = new stdClass();
                        $listitem->text = $item->get_text();
                        $listitem->url = $item->get_url();
                        $column->list[] = $listitem;
                    }
                    if (isset($theme->settings->$heading)) {
                        $column->heading = format_text($theme->settings->$heading, FORMAT_HTML);
                        $column->classheading = $heading;
                    }
                    $template->columns[] = $column;
                }
            }
        }

        if (count($template->columns) > 0) {
            $template->gridcount = (12 / (count($template->columns)));
        } else {
            $template->gridcount = 12;
        }

        return $this->render_from_template(
            'theme_pimenko/footercustomcontent', $template
        );
    }

    /**
     * Returns the URL for the favicon.
     *
     * @return string The favicon URL
     */
    public function favicon(): string {
        if (!empty($this->page->theme->settings->favicon)) {

            if (empty($this->themeconfig)) {
                $this->themeconfig = $theme = theme_config::load('pimenko');
            }
            return $this->themeconfig->setting_file_url(
                'favicon', 'favicon'
            );
        }
        return parent::favicon();
    }

    /**
     * Returns the google font set
     *
     * @return string Google font
     */
    public function googlefont(): string {
        if (!empty($this->page->theme->settings->googlefont)) {
            if (empty($this->themeconfig)) {
                $this->themeconfig = $theme = theme_config::load('pimenko');
            }
            return $this->page->theme->settings->googlefont;
        }
        // The default font we use if no settings define.
        return 'Verdana';
    }

    /**
     * Rendering the category menu in the header
     *
     * @return string
     */
    public function display_header_categories(): string {

        $theme = theme_config::load('pimenko');
        if (!empty($theme->settings->menuheadercateg) && $theme->settings->menuheadercateg != "disabled") {
            $cats = core_course_category::get_all();
            $template = new stdClass();
            $template->dropdownname = get_string('menuheadercateg', 'theme_pimenko');
            $template->dropdownitems = [];

            foreach ($cats as $cat) {
                if (!($theme->settings->menuheadercateg == 'excludehidden' && $cat->visible == 0) &&
                    $cat->get_parent_coursecat()->id == 0) {
                    $dropdownitem = new stdClass();
                    $dropdownitem->name = $cat->get_formatted_name();
                    $dropdownitem->url = $cat->get_view_link();
                    if ($cat->has_children()) {
                        $dropdownitem->submenu = $this->display_header_categories_recursively($cat);
                    }
                    $template->dropdownitems[] = $dropdownitem;
                }
            }
            return $this->render_from_template(
                'theme_pimenko/header_dropdown',
                $template);
        }
        return "";
    }

    /**
     * @param $category core_course_category
     * @return string
     */
    public function display_header_categories_recursively($category): string {
        $cats = $category->get_children();
        $template = new stdClass();
        $template->dropdownitems = [];
        $theme = theme_config::load('pimenko');

        foreach ($cats as $cat) {
            if (!($theme->settings->menuheadercateg == 'excludehidden' && $cat->visible == 0)) {
                $dropdownitem = new stdClass();
                $dropdownitem->name = $cat->get_formatted_name();
                $dropdownitem->url = $cat->get_view_link();
                if ($cat->get_children_count() > 0) {
                    $dropdownitem->submenu = $this->display_header_categories_recursively($cat);
                }
                $template->dropdownitems[] = $dropdownitem;
            }

        }

        return $this->render_from_template(
            'theme_pimenko/header_dropdown_recursive',
            $template);
    }

    /**
     * Renders the login form.
     *
     * @param login $form The renderable.
     *
     * @return string
     */
    public function render_login(login $form) {
        global $CFG, $SITE;

        $context = $form->export_for_template($this);

        // Override because rendering is not supported in template yet.
        if ($CFG->rememberusername == 0) {
            $context->cookieshelpiconformatted = $this->help_icon('cookiesenabledonlysession');
        } else {
            $context->cookieshelpiconformatted = $this->help_icon('cookiesenabled');
        }
        $context->errorformatted = $this->error_text($context->error);
        $url = $this->get_logo_url();
        if ($url) {
            $url = $url->out(false);
        }
        $context->logourl = $url;
        $context->sitename = format_string(
            $SITE->fullname, true, [
                'context' => context_course::instance(SITEID),
                "escape" => false
            ]
        );

        $context->logintextboxtop = self::get_setting('logintextboxtop', 'format_html');
        $context->logintextboxbottom = self::get_setting('logintextboxbottom', 'format_html');
        $context->rightblockloginhtmlcontent = self::get_setting('rightblockloginhtmlcontent', 'format_html');
        $context->leftblockloginhtmlcontent = self::get_setting('leftblockloginhtmlcontent', 'format_html');

        return $this->render_from_template('core/loginform', $context);
    }

    /**
     * Returns settings as formatted text
     *
     * @param string $setting
     * @param bool $format = false
     * @param string $theme = null
     *
     * @return string
     */
    public function get_setting($setting, $format = false, $theme = null) {
        if (empty($theme)) {
            $theme = theme_config::load('pimenko');
        }

        if (empty($theme->settings->$setting)) {
            return false;
        } else if (!$format) {
            return $theme->settings->$setting;
        } else if ($format === 'format_text') {
            return format_text($theme->settings->$setting, FORMAT_PLAIN);
        } else if ($format === 'format_html') {
            return format_text($theme->settings->$setting, FORMAT_HTML, ['trusted' => true]);
        } else {
            return format_string($theme->settings->$setting);
        }
    }

    /**
     * Render mod completion
     * If we're on a 'mod' page, retrieve the mod object and check it's completion state in order to conditionally
     * pop a completion modal and show a link to the next activity in the footer.
     *
     * @return string list of $mod, show completed activity (bool), and show completion modal (bool)
     */
    public function render_completion_footer(): string {
        global $COURSE;

        if ($COURSE->enablecompletion != COMPLETION_ENABLED
            || $this->page->pagelayout == "admin"
            || $this->page->pagetype == "course-editsection"
            || $this->page->bodyid == 'page-mod-quiz-attempt'
            || (isset($this->page->cm->completion) && !$this->page->cm->completion)
            || !isset($this->page->cm->completion)) {
            return '';
        }

        $renderer = $this->page->get_renderer(
            'core', 'course'
        );

        $completioninfo = new completion_info($COURSE);

        // Short-circuit if we are not on a mod page, and allow restful access.
        $pagepath = explode(
            '-', $this->page->pagetype
        );
        if ($pagepath[0] != 'mod') {
            return '';
        }
        if ($pagepath[2] == 'index') {
            return '';
        }
        // Make sure we have a mod object.
        $mod = $this->page->cm;
        if (!is_object($mod)) {
            return '';
        }

        // Get all course modules from modinfo.
        $cms = $mod->get_modinfo()->cms;

        $currentcmidfoundflag = false;
        $nextmod = false;
        // Loop through all course modules to find the next mod.
        foreach ($cms as $cmid => $cm) {
            // The nextmod must be after the current mod.
            // Keep looping until the current mod is found (+1).
            if (!$currentcmidfoundflag) {
                if ($cmid == $mod->id) {
                    $currentcmidfoundflag = true;
                }

                // Short circuit to next mod in list.
                continue;

            } else {
                // The continue and else condition are not mutually neccessary.
                // But the statement block is more clear with the explicit else).
                // The current activity has been found... set the next activity to the first.
                // User visible mod after this point.
                if ($cm->uservisible) {
                    $nextmod = $cm;
                    break;
                }
            }
        }
        $template = new stdClass();

        if ($nextmod) {
            $template->nextmodname = format_string($nextmod->name);
            $template->nextmodurl = $nextmod->url;
        }

        $theme = theme_config::load('pimenko');
        $moodlecompletion = $theme->settings->moodleactivitycompletion;
        if ($completioninfo->is_enabled($mod) && !$moodlecompletion) {
            $template->completionicon = $renderer->course_section_cm_completion(
                $COURSE, $completioninfo, $mod, ['showcompletiontext' => true]
            );
            return $renderer->render_from_template(
                'theme_pimenko/completionfooter', $template
            );
        }
        return '';
    }

    /**
     * Renders block regions on home page
     *
     * @return string
     */
    public function get_block_regions(): string {
        global $USER;

        $settingsname = 'blockrow';
        $fields = [];
        $retval = '';
        $blockcount = 0;
        $style = '';
        $adminediting = false;

        if (is_siteadmin() && isset($USER->editing) && $USER->editing == 1) {
            $style = '" style="display: block; background: #EEEEEE; min-height: 50px;
        border: 2px dashed #BFBDBD; margin-top: 5px';
            $adminediting = true;
        }
        for ($i = 1; $i <= 8; $i++) {
            $blocksrow = "{$settingsname}{$i}";
            $blocksrow = $this->page->theme->settings->$blocksrow;
            if ($blocksrow != '0-0-0-0') {
                $fields[] = $blocksrow;
            }
        }

        $i = 0;
        foreach ($fields as $field) {
            $retval .= '<div class="row front-page-row" id="front-page-row-' . ++$i . '">';
            $vals = explode(
                '-', $field
            );
            foreach ($vals as $val) {
                if ($val > 0) {
                    $retval .= "<div class=\"col-md-{$val}{$style}\">";

                    // Moodle does not seem to like numbers in region names so using letter instead.
                    $blockcount++;
                    $block = 'theme-front-' . chr(96 + $blockcount);

                    if ($adminediting) {
                        $retval .= '<span style="padding-left: 10px;"> ' . '' . '</span>';
                    }

                    $retval .= $this->blocks(
                        $block, 'block-region-front container-fluid'
                    );
                    $retval .= '</div>';
                }
            }
            $retval .= '</div>';
        }

        return $retval;
    }

    /**
     * Check if renderer is enabled.
     *
     * @return bool
     */
    public function is_carousel_enabled(): bool {
        if (empty($this->themeconfig)) {
            $this->themeconfig = $theme = theme_config::load('pimenko');
        }
        if (isset($this->themeconfig->settings->enablecarousel)
            && $this->themeconfig->settings->enablecarousel == 1) {
            return true;
        }
        return false;
    }

    /**
     * Init carousel renderer.
     *
     * @return string
     */
    public function carousel(): string {
        $carousel = $this->page->get_renderer('theme_pimenko', 'carousel');
        return $carousel->output();
    }

    /**
     * Returns standard navigation between activities in a course.
     *
     * @return string the navigation HTML.
     */
    public function activity_navigation() {
        // First we should check if we want to add navigation.
        $context = $this->page->context;
        if (($this->page->pagelayout !== 'incourse' && $this->page->pagelayout !== 'frametop')
            || $context->contextlevel != CONTEXT_MODULE || $this->page->bodyid == 'page-mod-quiz-attempt') {
            return '';
        }

        // If the activity is in stealth mode, show no links.
        if ($this->page->cm->is_stealth()) {
            return '';
        }

        $course = $this->page->cm->get_course();
        $courseformat = course_get_format($course);

        // Get a list of all the activities in the course.
        $modules = get_fast_modinfo($course->id)->get_cms();

        // Put the modules into an array in order by the position they are shown in the course.
        $mods = [];
        $activitylist = [];
        foreach ($modules as $module) {
            // Only add activities the user can access, aren't in stealth mode and have a url (eg. mod_label does not).
            if (!$module->uservisible || $module->is_stealth() || empty($module->url)) {
                continue;
            }
            $mods[$module->id] = $module;

            // No need to add the current module to the list for the activity dropdown menu.
            if ($module->id == $this->page->cm->id) {
                continue;
            }
            // Module name.
            $modname = $module->get_formatted_name();
            // Display the hidden text if necessary.
            if (!$module->visible) {
                $modname .= ' ' . get_string('hiddenwithbrackets');
            }
            // Module URL.
            $linkurl = new moodle_url($module->url, array('forceview' => 1));
            // Add module URL (as key) and name (as value) to the activity list array.
            $activitylist[$linkurl->out(false)] = $modname;
        }

        $nummods = count($mods);

        // If there is only one mod then do nothing.
        if ($nummods == 1) {
            return '';
        }

        // Get an array of just the course module ids used to get the cmid value based on their position in the course.
        $modids = array_keys($mods);

        // Get the position in the array of the course module we are viewing.
        $position = array_search($this->page->cm->id, $modids);

        $prevmod = null;
        $nextmod = null;

        // Check if we have a previous mod to show.
        if ($position > 0) {
            $prevmod = $mods[$modids[$position - 1]];
        }

        // Check if we have a next mod to show.
        if ($position < ($nummods - 1)) {
            $nextmod = $mods[$modids[$position + 1]];
        }

        $activitynav = new \core_course\output\activity_navigation($prevmod, $nextmod, $activitylist);
        $renderer = $this->page->get_renderer('core', 'course');
        return $renderer->render($activitynav);
    }

    /**
     *
     * Custom navbar primary items
     *
     * @return array
     */
    public function removedprimarynavitems(): array {
        $theme = theme_config::load('pimenko');

        if ($theme->settings->removedprimarynavitems) {
            return explode(',', $theme->settings->removedprimarynavitems);
        } else {
            return [];
        }
    }

    /**
     * Wrapper for header elements.
     *
     * @return string HTML to display the main header.
     */
    public function full_header() {
        $theme = theme_config::load('pimenko');
        $pagetype = $this->page->pagetype;
        $header = new stdClass();

        // Get cover course file.
        $files = false;
        $fs = get_file_storage();

        $filescoverimage = $fs->get_area_files(
            context_course::instance($this->page->course->id)->id,
            'theme_pimenko',
            'coverimage',
            0,
            "itemid, filepath, filename",
            false
        );

        if ($filescoverimage) {

            $oldfile = array_values($filescoverimage)[0];

            $urlcoverimage = moodle_url::make_pluginfile_url(
                $oldfile->get_contextid(),
                $oldfile->get_component(),
                $oldfile->get_filearea(),
                $oldfile->get_itemid(),
                $oldfile->get_filepath(),
                $oldfile->get_filename()
            );
            $header->urlcoverimage = $urlcoverimage;
        }

        if ($this->page->pagelayout == 'course' ||
            ($this->page->pagelayout == 'incourse' && $theme->settings->displaycoverallpage)) {
            $header->coverimagedata = [
                'id' => $this->page->course->id,
                'filename' => (isset($oldfile)) ? $oldfile->get_filename() : null,
                'withgradient' => (bool) $theme->settings->gradientcovercolor,
                'coverexist' => (bool) $filescoverimage,
                'displayasthumbnail' => (isset($header->urlcoverimage)) ? $theme->settings->displayasthumbnail : false,
                'seemenu' => $this->page->user_allowed_editing()
            ];
        }

        $homepage = get_home_page();
        $homepagetype = null;
        // Add a special case since /my/courses is a part of the /my subsystem.
        if ($homepage == HOMEPAGE_MY || $homepage == HOMEPAGE_MYCOURSES) {
            $homepagetype = 'my-index';
        } else if ($homepage == HOMEPAGE_SITE) {
            $homepagetype = 'site-index';
        }
        if ($this->page->include_region_main_settings_in_header_actions() &&
            !$this->page->blocks->is_block_present('settings')) {
            // Only include the region main settings if the page has requested it and it doesn't already have
            // the settings block on it. The region main settings are included in the settings block and
            // duplicating the content causes behat failures.
            $this->page->add_header_action(html_writer::div(
                $this->region_main_settings_menu(),
                'd-print-none',
                ['id' => 'region-main-settings-menu']
            ));
        }

        $header->settingsmenu = $this->context_header_settings_menu();
        $header->contextheader = $this->context_header();
        $header->hasnavbar = empty($this->page->layout_options['nonavbar']);
        $header->navbar = $this->navbar();
        $header->pageheadingbutton = $this->page_heading_button();
        $header->courseheader = $this->course_header();
        $header->headeractions = $this->page->get_header_actions();
        if (!empty($pagetype) && !empty($homepagetype) && $pagetype == $homepagetype) {
            $header->welcomemessage = \core_user::welcome_message();
        }
        return $this->render_from_template('core/full_header', $header);
    }

    /**
     * Renders the header bar.
     *
     * @param context_header $contextheader Header bar object.
     * @return string HTML for the header bar.
     */
    protected function render_context_header(\context_header $contextheader) {

        // Generate the heading first and before everything else as we might have to do an early return.
        if ($this->page->pagelayout == "incourse" || $this->page->pagelayout == "course") {
            $class = 'h2 pimenkocourseheader';
        } else {
            $class = 'h2';
        }
        if ($this->page->pagelayout == "coursecategory" && $this->themeconfig->settings->enablecatalog &&
            $this->themeconfig->settings->titlecatalog != "") {
            // Heading in the course index page with catalog activated.
            $heading = $this->heading(
                format_string($this->themeconfig->settings->titlecatalog),
                $contextheader->headinglevel
            );
        } else if (!isset($contextheader->heading)) {
            $heading = $this->heading($this->page->heading, $contextheader->headinglevel, $class);
        } else {
            $heading = $this->heading($contextheader->heading, $contextheader->headinglevel, $class);
        }

        // All the html stuff goes here.
        $html = html_writer::start_div('page-context-header');

        // Image data.
        if (isset($contextheader->imagedata)) {
            // Header specific image.
            $html .= html_writer::div($contextheader->imagedata, 'page-header-image mr-2');
        }

        // Headings.
        if (isset($contextheader->prefix)) {
            $prefix = html_writer::div($contextheader->prefix, 'text-muted text-uppercase small line-height-3');
            $heading = $prefix . $heading;
        }
        $html .= html_writer::tag('div', $heading, array('class' => 'page-header-headings'));

        // Buttons.
        if (isset($contextheader->additionalbuttons)) {
            $html .= html_writer::start_div('btn-group header-button-group');
            foreach ($contextheader->additionalbuttons as $button) {
                if (!isset($button->page)) {
                    // Include js for messaging.
                    if ($button['buttontype'] === 'togglecontact') {
                        \core_message\helper::togglecontact_requirejs();
                    }
                    if ($button['buttontype'] === 'message') {
                        \core_message\helper::messageuser_requirejs();
                    }
                    $image = $this->pix_icon($button['formattedimage'], $button['title'], 'moodle', array(
                        'class' => 'iconsmall',
                        'role' => 'presentation'
                    ));
                    $image .= html_writer::span($button['title'], 'header-button-title');
                } else {
                    $image = html_writer::empty_tag('img', array(
                        'src' => $button['formattedimage'],
                        'role' => 'presentation'
                    ));
                }
                $html .= html_writer::link($button['url'], html_writer::tag('span', $image), $button['linkattributes']);
            }
            $html .= html_writer::end_div();
        }
        $html .= html_writer::end_div();

        return $html;
    }
}
