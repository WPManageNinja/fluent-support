<?php

namespace FluentSupport\App\Hooks\Handlers;
use FluentSupport\App\Models\Meta;
use FluentSupport\App\Services\Helper;


class ReCaptchaHandler
{

    public static function getSettings()
    {
        $reCaptchaSettingsData = Meta::where('object_type', '_fs_recaptcha_settings')->first();

        return $reCaptchaSettingsData ? Helper::safeUnserialize($reCaptchaSettingsData->value, []) : [];
    }

    public static function isRecaptchaApplicable($formName, $settings = null)
    {
        // $settings can be pre-fetched via getSettings() to avoid a duplicate query
        $settings = $settings ?? static::getSettings();

        if (empty($settings['is_enabled']) || !filter_var($settings['is_enabled'], FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $formContainingReCaptcha = $settings['formContainingReCaptcha'] ?? [];

        return ($formContainingReCaptcha[$formName] ?? 'no') === 'yes';
    }

    public static function validateRecaptcha($token, $secret = null, $recaptchaVersion = null, $expectedAction = null)
    {

        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';

        if(!$secret){
            $settings = static::getSettings();
            if (!$settings) {
                return false;
            }
            $recaptchaVersion = $settings["reCaptcha_version"] ?? null;
            $secret = $settings['secretKey'] ?? '';
        }

        $response = wp_remote_post($verifyUrl, [
            'body'   => [
                'secret'   => $secret,
                'response' => $token
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($result['success'])) {
            return false;
        }

        if (!empty($result['hostname'])) {
            $expectedHost = wp_parse_url(site_url(), PHP_URL_HOST);
            if ($expectedHost && $result['hostname'] !== $expectedHost) {
                return false;
            }
        }

        if ('recaptcha_v3' === $recaptchaVersion) {
            if ($expectedAction) {
                $acceptedActions = [$expectedAction, 'submit'];

                if (!in_array($result['action'] ?? '', $acceptedActions, true)) {
                    return false;
                }
            }
            $score = $result['score'] ?? 0;
            $checkScore = apply_filters('fluent_support/recaptcha_v3_ref_score', 0.5);

            return $score >= $checkScore;
        }

        return true;
    }
}
