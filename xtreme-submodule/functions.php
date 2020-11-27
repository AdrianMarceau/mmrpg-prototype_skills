<?
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-ability_stat-boost_before' => function($objects){
        extract($objects);
        if (!$options->is_fixed_amount){
            if (!empty($options->extra_text)){ $options->extra_text .= ' <br /> '; }
            $options->extra_text .= $this_robot->print_name().'\'s '.$this_skill->print_name().' overclocks stat changes! ';
            $options->boost_amount = ceil(MMRPG_SETTINGS_STATS_MOD_MAX * 2);
        }
        return true;
    },
    'rpg-ability_stat-break_before' => function($objects){
        extract($objects);
        if (!$options->is_fixed_amount){
            if (!empty($options->extra_text)){ $options->extra_text .= ' <br /> '; }
            $options->extra_text .= $this_robot->print_name().'\'s '.$this_skill->print_name().' overclocks stat changes! ';
            $options->break_amount = ceil(MMRPG_SETTINGS_STATS_MOD_MAX * 2);
        }
        return true;
    }
);
?>
