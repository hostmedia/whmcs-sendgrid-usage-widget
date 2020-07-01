<?php

/**
 * Name: WHMCS SendGrid Usage Widget
 * Description: This widget provides you with your SendGrid Usage on your WHMCS admin dashboard.
 * Version 1.0
 * Created by Host Media Ltd
 * Website: https://www.hostmedia.co.uk/
 */

add_hook('AdminHomeWidgets', 1, function() {
    return new sendGridUsageWidget();
});

class sendGridUsageWidget extends \WHMCS\Module\AbstractWidget
{
    protected $title = 'SendGrid Usage';
    protected $description = 'Widget provides you with your SendGrid Usage/Stats on your admin dashboard. Created by Host Media.';
    protected $weight = 150;
    protected $columns = 1;
    protected $cache = true;
    protected $cacheExpiry = 120;

    public function getData()
    {
        // Config
        // You can generate your token using this URL: https://app.sendgrid.com/settings/api_keys
        $sendGridToken = '';
        
        // Date Period
        // SendGrid dashboard uses the start date of 7 days ago and the end date 1 day ago.
        $start_date = new DateTime('7 days ago');
        $end_date = new DateTime('1 days ago');
        
        // URL
        $sendGridApiUrl = 'https://api.sendgrid.com/v3/stats';
        
        // Curl
        $sendGridApiUrl .= '?start_date='.$start_date->format('Y-m-d').'&end_date='.$end_date->format('Y-m-d');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
            , 'Authorization: Bearer ' . $sendGridToken
        ));
        curl_setopt($ch, CURLOPT_URL, $sendGridApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // CURL Timeout (Seconds)
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        $result = json_decode($result);
        curl_close($ch);
        
        // Defaults
        $requests = 0;
        $delivered = 0;
        $opens = 0;
        $clicks = 0;
        $bounces = 0;
        $spam_reports = 0;

        if (!isset($result->errors)) {
            foreach ($result as &$stat) {
                $statItem = $stat->stats[0]->metrics;
                $requests += $statItem->requests;
                $delivered += $statItem->delivered;
                $opens += $statItem->opens;
                $clicks += $statItem->clicks;
                $bounces += $statItem->bounces;
                $spam_reports += $statItem->spam_reports;
            }
        }
        
        $dataArray = array(
            sendGrid => json_decode($result)
            , requests => $requests
            , delivered => $delivered
            , opens => $opens
            , clicks => $clicks
            , bounces => $bounces
            , spam_reports => $spam_reports
        );
        
        return $dataArray;
    }

    public function generateOutput($data)
    {
        
        // If sendgrid gives an error error
        if (isset($data['sendGrid']->errors)) {
            return <<<EOF
<div class="widget-content-padded">
    <strong>There was an error:</strong><br/>
    {$data['sendGrid']->errors[0]->message}
</div>
EOF;
        }
        
        return <<<EOF
<div class="widget-content widget-billing">
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-6 bordered-right">
                <div class="item">
                    <div class="data">{$data['requests']}</div>
                    <div class="note">Requests</div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="item">
                    <div class="data">{$data['delivered']}</div>
                    <div class="note">Delivered Emails</div>
                </div>
            </div>
            <div class="col-sm-6 bordered-right bordered-top">
                <div class="item">
                    <div class="data">{$data['opens']}</div>
                    <div class="note">Opened</div>
                </div>
            </div>
            <div class="col-sm-6 bordered-top">
                <div class="item">
                    <div class="data">{$data['clicks']}</div>
                    <div class="note">Clicked</div>
                </div>
            </div>
            <div class="col-sm-6 bordered-right bordered-top">
                <div class="item">
                    <div class="data">{$data['bounces']}</div>
                    <div class="note">Bounces</div>
                </div>
            </div>
            <div class="col-sm-6 bordered-top">
                <div class="item">
                    <div class="data">{$data['spam_reports']}</div>
                    <div class="note">SPAM Reports</div>
                </div>
            </div>
        </div>
    </div>
</div>
EOF;
    }
}
