<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property int $id
 * @property string $knack_id
 * @property string $spabooker_id
 * @property string $shopify_id
 * @property string $location_id
 * @property string $spabooker_status
 * @property int $is_failed
 * @property string $stripe_id
 * @property string $email
 * @property string $password
 * @property string $remember_token
 * @property string $created_at
 * @property string $updated_at
 * @property string $knack_updated_at
 * @property string $spabooker_created_at
 * @property string $spabooker_updated_at
 * @property string $first_name
 * @property string $last_name
 * @property string $phone
 * @property string $zip
 * @property string $origination
 * @property string $dob
 * @property string $push_notifications
 * @property string $device_id
 * @property int $reminder_opt_in
 * @property int $has_membership
 * @property string $membership_levels
 * @property string $number_of_referrals
 * @property string $referred_by_customer_id
 * @property int $is_refer_a_friend
 * @property string $heard_about_us
 * @property string $vi_squeeze
 * @property string $spabooker_data
 * @property string $refer_a_friend_nudge
 * @property string $refer_a_friend_nudge_date
 * @property string $appstore_rating_nudge
 * @property string $appstore_rating_nudge_date
 *
 * @property Address[] $addresses
 * @property Appointments[] $appointments
 * @property CustomerPreferences[] $customerPreferences
 * @property FavoriteTherapists[] $favoriteTherapists
 * @property TherapistReviews[] $therapistReviews
 */
class BaseModelDB2 extends \yii\db\ActiveRecord
{
    /**
     * Table name.
     * 
     * @var string
     */
    public static $table_name = null;

    public function __construct($table_name = null)
    {
        if ($table_name) {
            self::$table_name = $table_name;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDb()
    {
        return Yii::$app->db2;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return self::$table_name;
    }
}
