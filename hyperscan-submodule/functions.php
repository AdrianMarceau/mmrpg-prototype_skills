<?
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-robot_check-skills_battle-start' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Turn ON the ability to see skills/abilities during the scan
        $this_player->set_flag('hyperscan_enabled', true);

        // Only bother printing this message if the player is a human
        if ($this_player->player_autopilot === false){
            // Print a message showing that this effect is taking place
            $trigger_text = $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in! ';
            $trigger_text .= '<br /> Target robot abilities and skills can be scanned now! ';
            $this_skill->target_options_update(array('frame' => 'taunt', 'success' => array(9, 0, 0, 10, $trigger_text)));
            $this_robot->trigger_target($this_robot, $this_skill, array('prevent_default_text' => true));
        }

        // Return true on success
        return true;

    }
);
?>
