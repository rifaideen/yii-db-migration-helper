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
class Users extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['is_failed', 'reminder_opt_in', 'has_membership', 'is_refer_a_friend'], 'integer'],
            [['email', 'password', 'first_name', 'last_name', 'phone', 'dob'], 'required'],
            [['created_at', 'updated_at', 'knack_updated_at', 'spabooker_created_at', 'spabooker_updated_at', 'dob', 'refer_a_friend_nudge_date', 'appstore_rating_nudge_date'], 'safe'],
            [['spabooker_data'], 'string'],
            [['knack_id', 'spabooker_id', 'location_id', 'stripe_id', 'email', 'password', 'first_name', 'last_name', 'phone', 'refer_a_friend_nudge', 'appstore_rating_nudge'], 'string', 'max' => 191],
            [['shopify_id', 'spabooker_status', 'zip', 'origination', 'push_notifications', 'device_id', 'membership_levels', 'number_of_referrals', 'referred_by_customer_id', 'heard_about_us', 'vi_squeeze'], 'string', 'max' => 250],
            [['remember_token'], 'string', 'max' => 100],
            [['email'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'knack_id' => 'Knack ID',
            'spabooker_id' => 'Spabooker ID',
            'shopify_id' => 'Shopify ID',
            'location_id' => 'Location ID',
            'spabooker_status' => 'Spabooker Status',
            'is_failed' => 'Is Failed',
            'stripe_id' => 'Stripe ID',
            'email' => 'Email',
            'password' => 'Password',
            'remember_token' => 'Remember Token',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'knack_updated_at' => 'Knack Updated At',
            'spabooker_created_at' => 'Spabooker Created At',
            'spabooker_updated_at' => 'Spabooker Updated At',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'phone' => 'Phone',
            'zip' => 'Zip',
            'origination' => 'Origination',
            'dob' => 'Dob',
            'push_notifications' => 'Push Notifications',
            'device_id' => 'Device ID',
            'reminder_opt_in' => 'Reminder Opt In',
            'has_membership' => 'Has Membership',
            'membership_levels' => 'Membership Levels',
            'number_of_referrals' => 'Number Of Referrals',
            'referred_by_customer_id' => 'Referred By Customer ID',
            'is_refer_a_friend' => 'Is Refer A Friend',
            'heard_about_us' => 'Heard About Us',
            'vi_squeeze' => 'Vi Squeeze',
            'spabooker_data' => 'Spabooker Data',
            'refer_a_friend_nudge' => 'Refer A Friend Nudge',
            'refer_a_friend_nudge_date' => 'Refer A Friend Nudge Date',
            'appstore_rating_nudge' => 'Appstore Rating Nudge',
            'appstore_rating_nudge_date' => 'Appstore Rating Nudge Date',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddresses()
    {
        return $this->hasMany(Address::className(), ['customer_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAppointments()
    {
        return $this->hasMany(Appointments::className(), ['customer_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomerPreferences()
    {
        return $this->hasMany(CustomerPreferences::className(), ['customer_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFavoriteTherapists()
    {
        return $this->hasMany(FavoriteTherapists::className(), ['customer_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTherapistReviews()
    {
        return $this->hasMany(TherapistReviews::className(), ['user_id' => 'id']);
    }
}
