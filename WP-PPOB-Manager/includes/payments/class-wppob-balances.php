<?php
defined('ABSPATH') || exit;

class WPPOB_Balances {
    
    /**
     * Get user's current balance.
     */
    public static function get_user_balance($user_id) {
        return (float) get_user_meta($user_id, '_wppob_balance', true);
    }

    /**
     * Update user's balance.
     */
    private static function set_user_balance($user_id, $new_balance) {
        update_user_meta($user_id, '_wppob_balance', $new_balance);
    }

    /**
     * Add funds to a user's balance.
     */
    public static function add_balance($user_id, $amount, $description = '') {
        $current_balance = self::get_user_balance($user_id);
        $new_balance = $current_balance + (float)$amount;
        self::set_user_balance($user_id, $new_balance);
        self::add_mutation_log($user_id, $amount, 'credit', $description);
        return true;
    }
    
    /**
     * Deduct funds from a user's balance.
     */
    public static function deduct_balance($user_id, $amount, $description = '') {
        $current_balance = self::get_user_balance($user_id);
        if ($current_balance < $amount) {
            return false; // Insufficient balance
        }
        $new_balance = $current_balance - (float)$amount;
        self::set_user_balance($user_id, $new_balance);
        self::add_mutation_log($user_id, $amount, 'debit', $description);
        return true;
    }

    /**
     * Log the balance mutation to the database.
     */
    private static function add_mutation_log($user_id, $amount, $type, $description = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wppob_balance_mutations';
        
        $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'created_at' => current_time('mysql'),
        ]);
    }
}
