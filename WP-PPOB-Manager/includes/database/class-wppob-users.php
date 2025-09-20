<?php
defined('ABSPATH') || exit;

class WPPOB_Users {

    public static function search_users($search_term) {
        $users = get_users([
            'search' => '*' . esc_attr($search_term) . '*',
            'search_columns' => ['user_login', 'user_email'],
            'number' => 10
        ]);
        return $users;
    }

    public static function get_user_details($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'balance' => wppob_format_rp(WPPOB_Balances::get_user_balance($user->ID)),
        ];
    }
    
    
    
    public static function get_recent_users($limit = 20) {
        $users = get_users([
            'number' => $limit,
            'orderby' => 'user_registered',
            'order' => 'DESC'
        ]);
        return $users;
    }
    
    
}
