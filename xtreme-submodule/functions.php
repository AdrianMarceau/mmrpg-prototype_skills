<?
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'skill_function_onload' => function($objects){
        extract($objects);
        $this_skill->priority = -5;
        return true;
    },
    'rpg-ability_stat-boost_before' => function($objects){
        extract($objects);
        if ($this_robot !== $recipient_robot){ return; }
        if (!$options->is_fixed_amount){
            $trigger_text = $this_robot->print_name().'\'s '.$this_skill->print_name().' overclocks stat changes! ';
            if (!empty($options->extra_text)){ $options->extra_text .= ' <br /> '; }
            $options->extra_text .= $trigger_text;
            $invert_boost = $options->boost_amount < 0 ? true : false;
            $options->boost_amount = ceil(MMRPG_SETTINGS_STATS_MOD_MAX * 2);
            if ($invert_boost){ $options->boost_amount *= -1; }
        }
        return true;
    },
    'rpg-ability_stat-break_before' => function($objects){
        extract($objects);
        if ($this_robot !== $recipient_robot){ return; }
        if (!$options->is_fixed_amount){
            $trigger_text = $this_robot->print_name().'\'s '.$this_skill->print_name().' overclocks stat changes! ';
            if (!empty($options->extra_text)){ $options->extra_text .= ' <br /> '; }
            $options->extra_text .= $trigger_text;
            $invert_break = $options->break_amount < 0 ? true : false;
            $options->break_amount = ceil(MMRPG_SETTINGS_STATS_MOD_MAX * 2);
            if ($invert_break){ $options->break_amount *= -1; }
        }
        return true;
    }
);
?>
