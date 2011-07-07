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
 * Main group self selection interface
 *
 * @package    mod
 * @subpackage groupselect
 * @copyright  2008 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('locallib.php');
require_once('signup_form.php');

$id      = optional_param('id', 0, PARAM_INT);       // Course Module ID, or
$g       = optional_param('g', 0, PARAM_INT);        // Page instance ID
$signup  = optional_param('signup', 0, PARAM_INT);
$signout = optional_param('signout', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

if ($g) {
    $groupselect = $DB->get_record('groupselect', array('id'=>$g), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('groupselect', $groupselect->id, $groupselect->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('groupselect', $id, 0, false, MUST_EXIST);
    $groupselect = $DB->get_record('groupselect', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

add_to_log($course->id, 'groupselect', 'view', 'view.php?id='.$cm->id, $groupselect->id, $cm->id);

$PAGE->set_url('/mod/groupselect/view.php', array('id' => $cm->id));
$PAGE->set_title($course->shortname.': '.$groupselect->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($groupselect);

$mygroups       = groups_get_all_groups($course->id, $USER->id, $groupselect->targetgrouping, 'g.id');
$isopen         = groupselect_is_open($groupselect);
$groupmode      = groups_get_activity_groupmode($cm, $course);
$counts         = groupselect_group_member_counts($cm, $groupselect->targetgrouping);
$groups         = groups_get_all_groups($course->id, 0, $groupselect->targetgrouping);
$accessall      = has_capability('moodle/site:accessallgroups', $context);
$viewfullnames  = has_capability('moodle/site:viewfullnames', $context);
$canselect      = (has_capability('mod/groupselect:select', $context) and is_enrolled($context) and empty($mygroups));
$canunselect    = (has_capability('mod/groupselect:unselect', $context) and is_enrolled($context) and !empty($mygroups));

if ($course->id == SITEID) {
    $viewothers = has_capability('moodle/site:viewparticipants', $context);
} else {
    $viewothers = has_capability('moodle/course:viewparticipants', $context);
}

$strgroup       = get_string('group');
$strgroupdesc   = get_string('groupdescription', 'group');
$strmembers     = get_string('memberslist', 'mod_groupselect');
$strsignup      = get_string('signup', 'mod_groupselect');
$strsignout     = get_string('signout', 'mod_groupselect');
$straction      = get_string('action', 'mod_groupselect');
$strcount       = get_string('membercount', 'mod_groupselect');

if ($accessall) {
    // can see group members - depending on overrides guests could get through too

} else if (isguestuser()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('noguestselect', 'mod_groupselect'));
    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id'=>$course->id)));
    echo $OUTPUT->footer($course);
    exit;

} else if (!is_enrolled($context)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('noenrolselect', 'mod_groupselect'));
    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id'=>$course->id)));
    echo $OUTPUT->footer($course);
    exit;
}

if ($signup and $canselect and isset($groups[$signup]) and $isopen) {
    // user selected group
    $data = array('id'=>$id, 'signup'=>$signup);
    $mform = new signup_form(null, array($data, $groupselect));

    if ($mform->is_cancelled()) {
        redirect($PAGE->url);

    } else if ($mform->get_data()) {
        groups_add_member($signup, $USER->id);
        add_to_log($course->id, 'groupselect', 'select', 'view.php?id='.$cm->id, $groupselect->id, $cm->id);
        redirect($PAGE->url);

    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('signup', 'mod_groupselect'));
        echo $OUTPUT->box(get_string('signupconfirm', 'mod_groupselect', format_string($groups[$signup]->name, true, array('context'=>$context))));
        $mform->display();
        echo $OUTPUT->footer();
        die;
    }

} else if ($signout and $canunselect and isset($mygroups[$signout]) and $isopen) {
    // user unselected group

    if ($confirm and data_submitted() and confirm_sesskey()) {
        groups_remove_member($signout, $USER->id);
        add_to_log($course->id, 'groupselect', 'unselect', 'view.php?id='.$cm->id, $groupselect->id, $cm->id);
        redirect($PAGE->url);

    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('signout', 'mod_groupselect'));
        $yesurl = new moodle_url('/mod/groupselect/view.php', array('id'=>$cm->id, 'signout'=>$signout, 'confirm'=>1,'sesskey'=>sesskey()));
        $message = get_string('signoutconfirm', 'mod_groupselect', format_string($groups[$signout]->name, true, array('context'=>$context)));
        echo $OUTPUT->confirm($message, $yesurl, $PAGE->url);
        echo $OUTPUT->footer();
        die;
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($groupselect->name, true, array('context'=>$context)));

if (trim(strip_tags($groupselect->intro))) {
    echo $OUTPUT->box_start('mod_introbox', 'groupselectintro');
    echo format_module_intro('page', $groupselect, $cm->id);
    echo $OUTPUT->box_end();
}

if ($canselect and $groupselect->timeavailable > time()) {
    echo $OUTPUT->notification(get_string('notavailableyet', 'mod_groupselect', userdate($groupselect->timeavailable)), "$CFG->wwwroot/course/view.php?id=$course->id");

} else if ($canselect and $groupselect->timedue != 0 and  $groupselect->timedue < time() and empty($mygroups)) {
    echo $OUTPUT->notification(get_string('notavailableanymore', 'mod_groupselect', userdate($groupselect->timedue)));

} else if (!is_enrolled($context)) {
    // TODO: explain no select possible if not enrolled
}

if ($groups) {
    $data = array();
    $actionpresent = false;

    foreach ($groups as $group) {
        $ismember  = isset($mygroups[$group->id]);
        $usercount = isset($counts[$group->id]) ? $counts[$group->id]->usercount : 0;
        $grpname   = format_string($group->name, true, array('context'=>$context));

        $line = array();
        if ($ismember) {
            $grpname = '<div class="mygroup">'.$grpname.'</div>';
        }
        $line[0] = $grpname;
        $line[1] = groupselect_get_group_info($group);

        if ($groupselect->maxmembers) {
            $line[2] = $usercount.'/'.$groupselect->maxmembers;
        } else {
            $line[2] = $usercount;
        }

        if ($accessall) {
            $canseemembers = true;
        } else {
            if ($groupmode == SEPARATEGROUPS and !$ismember) {
                $canseemembers = false;
            } else {
                $canseemembers = $viewothers;
            }
        }

        if ($canseemembers) {
            if ($members = groups_get_members($group->id)) {
                $membernames = array();
                foreach ($members as $member) {
                    $pic = $OUTPUT->user_picture($member, array('courseid'=>$course->id));
                    if ($member->id == $USER->id) {
                        $membernames[] = '<span class="me">'.$pic.'&nbsp;'.fullname($member, $viewfullnames).'</span>';
                    } else {
                        $membernames[] = $pic.'&nbsp;<a href="'.$CFG->wwwroot.'/user/view.php?id='.$member->id.'&amp;course='.$course->id.'">'.fullname($member, $viewfullnames).'</a>';
                    }
                }
                $line[3] = implode(', ', $membernames);
            } else {
                $line[3] = '';
            }
        } else {
            $line[3] = '<div class="membershidden">'.get_string('membershidden', 'mod_groupselect').'</div>';
        }
        if ($isopen and !$accessall) {
            if (!$ismember and $groupselect->maxmembers and $groupselect->maxmembers <= $usercount) {
                $line[4] = '<div class="maxlimitreached">'.get_string('maxlimitreached', 'mod_groupselect').'</div>'; // full - no more members
                $actionpresent = true;
            } else if ($ismember and $canunselect) {
                $line[4] = "<a title=\"$strsignout\" href=\"view.php?id=$cm->id&amp;signout=$group->id\">$strsignout</a>";
                $actionpresent = true;
            } else if (!$ismember and $canselect) {
                $line[4] = "<a title=\"$strsignup\" href=\"view.php?id=$cm->id&amp;signup=$group->id\">$strsignup</a> ";
                $actionpresent = true;
            }
        }
        $data[] = $line;
    }

    $table = new html_table();
    $table->head  = array($strgroup, $strgroupdesc, $strcount, $strmembers);
    $table->size  = array('10%', '30%', '5%', '55%');
    $table->align = array('left', 'center', 'left', 'left');
    $table->data  = $data;
    if ($actionpresent) {
        $table->head[]  = $straction;
        $table->size    = array('10%', '30%', '5%', '45%', '10%');
        $table->align[] = 'center';
    }
    echo html_writer::table($table);

} else {
    echo $OUTPUT->notification(get_string('nogroups', 'mod_groupselect'));
}


echo $OUTPUT->footer();


