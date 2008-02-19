<?php  // $Id$

/**
 * Common definitions of $cap_* variables for Stamp Collection module
 *
 * @author David Mudrak
 * @package mod/stampcoll
 */

    if (empty($course) or empty($context)) {
        die('You cannot call this script in that way');
    }

/// Get capabilities. Somewhere we want to ignore admin's doanything
    // if you can't collect, you can't view your own stamps
    $cap_viewownstamps = has_capability('mod/stampcoll:collectstamps', $context, NULL, false) 
                        && has_capability('mod/stampcoll:viewownstamps', $context, NULL, false);
    $cap_viewotherstamps = has_capability('mod/stampcoll:viewotherstamps', $context);
    $cap_viewsomestamps = $cap_viewownstamps || $cap_viewotherstamps;
    $cap_viewonlyownstamps = $cap_viewownstamps && (!$cap_viewotherstamps);

    $cap_givestamps = has_capability('mod/stampcoll:givestamps', $context);
    // XXX this is going to be computed as edit || remove || update etc.
    $cap_editstamps = $cap_givestamps;

?>
