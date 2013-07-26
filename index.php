<?php
$simplified = array(
    'publicKey' =>      '',
    'privateKey' =>     ''
);
// That's it, Simplify, simplified.

/* SIMPLIFIED
 * GETTING STARTED
 * 1. Copy your API keys from Simplify.com (Make sure you use your sandbox keys!)
 *    https://www.simplify.com/commerce/app#/account/apiKeys
 * 2. Open index.php in any text editor.
 * 3. Paste the keys between the quotes:
 *      $simplified = array(
 *          'publicKey' =>      'sb_jf7uio3j8qf4io1qjewf434fq',
 *          'publicKey' =>      'dsa87943fqjo48qf3vj428optjop8tvvrq8'
 *      );
 * 4. Upload this script & begin processing!
 */

/* ADDITIONAL SETTINGS
 * Once you have had a chance to explore, fill in your details
 * below. This will transform your application, removing the
 * included guides/references, replacing copy with your information,
 * leaving you with a simple public payment form.
 */

$simplified['title']        = '';
$simplified['description']  = '';
$simplified['company']      = 'Your Company';
$simplified['amount']       = '999';

/* DONE! */

session_start();

// URL Rerouting (Variable Transaction Amount)
$protocol = 'http'.(!empty($_SERVER['HTTPS']) ? 's' : '');
$root = $protocol.'://'.$_SERVER['SERVER_NAME'];
$simplified['url'] = $root . $_SERVER['PHP_SELF'];

if(isset($_REQUEST['amount']) && is_int(intval($_REQUEST['amount'])) && $_REQUEST['amount'] >= 51) {
    if($_GET['amount']) {
        $_SESSION['amount'] = $_REQUEST['amount'];
        header('Location: ' . $simplified['url']);
        die();
    }
} elseif($_SESSION['amount']) {
    $simplified['amount'] = $_SESSION['amount'];
}

// Check API Keys & Their Prefixes
// Determine Environment
if(!empty($simplified['publicKey']) || !empty($simplified['privateKey'])) {

    $publicKeyPrefix = substr($simplified['publicKey'], 0, 2);
    $privateKeyPrefix = substr($simplified['privateKey'], 0, 2);
    
    require_once('simplifycommerce-sdk-php/lib/Simplify.php');

    Simplify::$publicKey = $simplified['publicKey'];
    Simplify::$privateKey = $simplified['privateKey'];

    $simplified['errorHandling'] = array(
        'system' => 'System error occurred processing a request.',
        'no.payment.details' => 'No token, card or customer details in payment request.',
        'expYear.expired' => 'The card expiry year is invalid.',
        'expMonth.expired' => 'The card expiry month is invalid.',
        'card.invalid' => 'The supplied card is invalid.',
        'card.expired' => 'The card has expired.',
        'object.not.found' => 'Not Found.',
        'operation.not.allowed' => 'Operation Not Allowed.',
        'user.not.authorized' => 'User not authorized.',
        'service.unavailable' => 'The service you requested is currently unavailable.',
        'auth.bad.jws' => 'JWS encoding of request message is invalid or missing.',
        'auth.bad.algol' => 'Invalid JWS algorithm used in request message.',
        'auth.bad.kid' => 'Invalid or missing public key in request message.',
        'auth.bad.uri' => 'Invalid or missing URI in JWS request message.',
        'auth.bad.timestamp' => 'Invalid or missing timestamp in JWS request message.',
        'auth.bad.nonce' => 'Invalid of missing nonce in JWS request message.',
        'auth.invalid.keys' => 'Invalid or missing API keys.',
        'auth.bad.sig' => 'Cannot verify JWS signature in request message.',
        'auth.invalid.account.mode' => 'The account you are using is only in test mode.  You can activate your account in the Dashboard.',
        'auth.invalid.token' => 'Card token is invalid.',
        'validation' => 'Request data is invalid.',
        'invoice.item.paid' => 'Unable to complete action as the invoice is already paid.',
        'coupon.does.not.exist' => 'Coupon not found.',
        'plan.not.found' => 'Subscription plan not found.',
        'coupon.expired' => 'Coupon has expired.',
        'coupon.not.active' => '.',
        'analytic.data.invalid' => 'Analytic paramters invalid.',
        'refund.insufficient.balance' => 'The amount you are trying to refund is greater than the remaining balance.',
        'criteria.invalid' => 'Invalid arguments in criteria.'
    );

    try {
        $webhook = Simplify_Webhook::listWebhook();
        if($webhook) {
            if($publicKeyPrefix == 'sb') {
                $simplified['mode'] = 'sandbox';
            } elseif($publicKeyPrefix == 'lv') {
                $simplified['mode'] = 'live';
            }
            if($_POST) {
                try {
                 
                    $payment = Simplify_Payment::createPayment(array(
                        "token" => $_POST['simplifyToken'],
                        "currency" => "USD",
                        "amount" => $_POST['amountInt'],
                        "description" => $_POST['description']
                    ));

                    if($payment) {
                        if($payment->paymentStatus == 'APPROVED') {
                            $simplified['success'] = 'Your payment has been approved.';
                            $_SESSION['amount'] = null;
                            session_destroy();
                        } else {
                            $simplified['error'] = 'Your payment was not approved: ' . $payment->paymentStatus;
                        }
                    } else {
                        $simplified['error'] = 'There was an unexpected error.';
                    }
                 
                } catch (Simplify_ApiException $e) {
                    if ($e instanceof Simplify_BadRequestException && $e->hasFieldErrors()) {
                        foreach ($e->getFieldErrors() as $fieldError) {
                            if(!empty($simplified['errorHandling'][$fieldError->getErrorCode()])) {
                                $simplified['error'] .= $simplified['errorHandling'][$fieldError->getErrorCode()];
                            }
                        }
                        if(empty($simplified['error'])) $simplified['error'] = 'There was an unexpected error processing your payment.';
                    } else {
                        $simplified['error'] = 'There was an error processing your payment.';
                    }

                    if($simplified['mode'] == 'sandbox') {
                        $simplified['debug'] = "Reference:   " . $e->getReference() . "\n";
                        $simplified['debug'] .= "Message:     " . $e->getMessage() . "\n";
                        $simplified['debug'] .= "Error code:  " . $e->getErrorCode() . "\n";
                        if ($e instanceof Simplify_BadRequestException && $e->hasFieldErrors()) {
                            foreach ($e->getFieldErrors() as $fieldError) {
                                $simplified['debug'] .= $fieldError->getFieldName()
                                    . ": '" . $fieldError->getMessage()
                                    . "' (" . $fieldError->getErrorCode()
                                    . ")\n";
                            }
                        }
                    }
                }
            }
        } else {
            $simplified['mode'] = 'invalid';
        }
    } catch (Simplify_ApiException $e) {
        $simplified['mode'] = 'invalid';
    }

} else {
    $simplified['mode'] = 'setup';
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title><?php echo $simplified['title']; ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta name="description" content="<?php echo $simplified['description']; ?>">
        <meta name="author" content="<?php echo $simplified['company']; ?>">
        <meta name="format-detection" content="telephone=no">

        <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.no-icons.min.css" rel="stylesheet">
        <link href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.min.css" rel="stylesheet">
        <link href="//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome-ie7.min.css" rel="stylesheet">

        <style type="text/css">
            /* DOCUMENT */
            html, body { height: 100%; }

            body { background: #CCC url(https://www.simplify.com/commerce/static/images/bg-gradient.png); }


            /* GLOBAL STYLES */
            .container a, i { color: #F26722; }
            .nav-tabs .active i { color: #000; }

            a h4, a i.icon-large {
                display: block;
                text-align: center;
                text-shadow: 0px 1px 0px #EEE;
                color: #F26722;
            }

            i.icon-large {
                text-decoration: none;
            }

            .alert i {
                color: #333333;
            }

            /* HEADER */
            #wrap {
                min-height: 100%;
                height: auto !important;
                margin: 0 auto -60px;
            }

                .navbar-fixed-top {
                    margin-bottom: 0px !important;
                }

                    .navbar-fixed-top .pull-right {
                        margin-right: 15px;
                        padding-top: 8px;
                    }

                    .navbar-fixed-top .pull-left {
                        margin-left: 15px;
                    }

                    .brand {
                        float: none !important;
                        margin: 0 auto;
                        text-shadow: 0px 1px 0px #000;
                        color: #fff !important;
                    }

            /* INTRO & FORM */
            #top {
                padding-top: 125px;
                padding-bottom: 75px;
                text-shadow: 0px 1px 0px #fff;
            }

                #form {
                    padding-top: 30px;
                }

                    #form form {
                        position: relative;     
                        padding: 15px;
                        background-color: #fff;
                        border: 1px solid #fff;
                        -webkit-border-radius: 5px;
                        -moz-border-radius: 5px;
                        border-radius: 5px;  
                        -webkit-box-shadow:0 1px 4px rgba(0, 0, 0, 0.3), 0 0 40px rgba(0, 0, 0, 0.1) inset;
                        -moz-box-shadow:0 1px 4px rgba(0, 0, 0, 0.3), 0 0 40px rgba(0, 0, 0, 0.1) inset;
                        box-shadow:0 1px 4px rgba(0, 0, 0, 0.3), 0 0 40px rgba(0, 0, 0, 0.1) inset;
                    }

                        #form form:before, #form form:after {
                            content: "";
                            position: absolute; 
                            z-index: -1;
                            -webkit-box-shadow: 0 0 20px rgba(0,0,0,0.8);
                            -moz-box-shadow: 0 0 20px rgba(0,0,0,0.8);
                            box-shadow: 0 0 20px rgba(0,0,0,0.8);
                            top: 50%;
                            bottom: 0;
                            left: 10px;
                            right: 10px;
                            -moz-border-radius: 100px / 10px;
                            border-radius: 100px / 10px;
                        } 

                        #form form:after {
                            right: 10px; 
                            left: auto;
                            -webkit-transform:skew(8deg) rotate(3deg); 
                            -moz-transform:skew(8deg) rotate(3deg);     
                            -ms-transform:skew(8deg) rotate(3deg);     
                            -o-transform:skew(8deg) rotate(3deg); 
                            transform:skew(8deg) rotate(3deg);
                        }

                        #form form button i { color: #000; text-shadow: 0px 4px 3px 5px #FFF; }

                        #form #description { background: #EEE; }

                    .brand-marks {
                        width: 180px;
                        height: 16px;
                        background-color: #eee;
                        background-repeat: no-repeat;
                        background-position: 5px 5px;
                        background-size: 180px 16px;
                        background-image: url("https://simplify.com/commerce/images/landing-page/brandmarks.jpg");
                        margin-top: 10px;
                        margin-left: auto;
                        margin-right: auto;
                        padding: 5px;
                        border: 1px solid #fff;
                        -webkit-border-radius: 5px;
                        -moz-border-radius: 5px;
                        border-radius: 5px;
                    }

            /* TABS */
            .nav-wrapper, .nav-tabs {
                width: auto !important;
                margin: 0px !important;
                padding: 0px !important;
                text-align: center;
            }

            .nav-wrapper {
                width: 100%;
                padding-top: 15px !important;
                padding-bottom: 0px !important;
                background: rgba(255,255,255,0.4);
                -webkit-border-top-left-radius: 4px;
                -webkit-border-top-right-radius: 4px;
                -moz-border-radius-topleft: 4px;
                -moz-border-radius-topright: 4px;
                border-top-left-radius: 4px;
                border-top-right-radius: 4px;
            }

                .nav-tabs li {
                    display: inline-block;
                    float: none;
                    margin-left: 5px;
                    margin-right: 5px;
                }

                    .nav-tabs li a {
                    padding-left: 15px;
                    padding-right: 15px;
                        font-size: 16px;
                        line-height: 40px !important;
                    }

            .tab-content {
                margin: 0px !important;
                margin-bottom: 50px !important;
                padding: 0px !important;
            }

            .tab-pane {
                background: #FFF;
                margin: 0px !important;
                padding: 50px !important;
                -webkit-border-bottom-right-radius: 4px;
                -webkit-border-bottom-left-radius: 4px;
                -moz-border-radius-bottomright: 4px;
                -moz-border-radius-bottomleft: 4px;
                border-bottom-right-radius: 4px;
                border-bottom-left-radius: 4px;
            }

                .hidden-middesk { display: none; }

                #gettingStartedWrapper {
                    width: 400px;
                    max-width: 100%;
                    margin: 0 auto;
                }

                #classReference p, #resources p {
                    font-size: 9px;
                }

            /* FOOTER */
            #push, #footer { height: 60px; }

            #footer { background-color: #f5f5f5; }

                #footer > .container {
                    font-size: 10px;
                    line-height: 10px;
                    padding-top: 25px;
                }

            /* RESPONSIVE */
                /* Large desktop */
                @media (min-width: 1200px) {
                }
                 
                /* Portrait tablet to landscape and desktop */
                @media (min-width: 768px) and (max-width: 979px) {
                }
                 
                /* Landscape phone to portrait tablet */
                @media (max-width: 767px) {
                    #top { padding-top: 25px; padding-bottom: 25px; }
                    #intro { padding: 30px; }
                    #form { padding-top: 0px; }
                    form { margin: 0 auto; max-width: 200px; }
                    #footer {
                        margin-left: -20px;
                        margin-right: -20px;
                        padding-left: 20px;
                        padding-right: 20px;
                    }
                    .tab-pane p { font-size: 12px;}
                    #classReference p,
                    #resources p {
                        padding-left: 50px;
                    }

                    .tab-pane p.lead { font-size: 12px; }
                    .tab-pane pre { font-size: 8px !important; }
                    .hidden-middesk { display: block; }
                    .tab-pane a { }

                    #classReference a i,
                    #resources a i { float: left; width: 50px; text-align: center; }
                    .tab-pane a h4 { margin: 0px; font-size: 14px; text-align: left; }

                    .row-fluid .mobile-one {
                        width: 31.491712707182323%;
                        *width: 31.43852121782062%;
                    }

                    .row-fluid .mobile-three {
                        width: 65.74585635359117%;
                        *width: 65.69266486422946%;
                    }

                    .row-fluid .mobile-two {
                        width: 48.61878453038674%;
                        *width: 48.56559304102504%;
                    }

                    .row-fluid .mobile-one,
                    .row-fluid .mobile-two,
                    .row-fluid .mobile-three {
                        float: left;
                        margin-left: 2.7624309392265194%;
                        *margin-left: 2.709239449864817%;
                    }
                }
                 
                /* Landscape phones and down */
                @media (max-width: 480px) {
                    form { max-width: 100%; }
                    .tab-pane { padding: 15px !important; }
                    .nav-wrapper { padding-top: 15px !important; }
                }

            /* GRADIENTS */
            #top {
                background: -moz-radial-gradient(center, ellipse cover,  rgba(255,255,255,1) 0%, rgba(255,255,255,0) 50%, rgba(255,255,255,0) 100%); /* FF3.6+ */
                background: -webkit-gradient(radial, center center, 0px, center center, 100%, color-stop(0%,rgba(255,255,255,1)), color-stop(50%,rgba(255,255,255,0)), color-stop(100%,rgba(255,255,255,0))); /* Chrome,Safari4+ */
                background: -webkit-radial-gradient(center, ellipse cover,  rgba(255,255,255,1) 0%,rgba(255,255,255,0) 50%,rgba(255,255,255,0) 100%); /* Chrome10+,Safari5.1+ */
                background: -o-radial-gradient(center, ellipse cover,  rgba(255,255,255,1) 0%,rgba(255,255,255,0) 50%,rgba(255,255,255,0) 100%); /* Opera 12+ */
                background: -ms-radial-gradient(center, ellipse cover,  rgba(255,255,255,1) 0%,rgba(255,255,255,0) 50%,rgba(255,255,255,0) 100%); /* IE10+ */
                background: radial-gradient(ellipse at center,  rgba(255,255,255,1) 0%,rgba(255,255,255,0) 50%,rgba(255,255,255,0) 100%); /* W3C */
                filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#00ffffff',GradientType=1 ); /* IE6-9 fallback on horizontal gradient */
            }

            #wrap > .container {
                background: -moz-radial-gradient(center, ellipse cover,  rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%); /* FF3.6+ */
                background: -webkit-gradient(radial, center center, 0px, center center, 100%, color-stop(0%,rgba(255,255,255,1)), color-stop(100%,rgba(255,255,255,0))); /* Chrome,Safari4+ */
                background: -webkit-radial-gradient(center, ellipse cover,  rgba(255,255,255,1) 0%,rgba(255,255,255,0) 100%); /* Chrome10+,Safari5.1+ */
                background: -o-radial-gradient(center, ellipse cover,  rgba(255,255,255,1) 0%,rgba(255,255,255,0) 100%); /* Opera 12+ */
                background: -ms-radial-gradient(center, ellipse cover,  rgba(255,255,255,1) 0%,rgba(255,255,255,0) 100%); /* IE10+ */
                background: radial-gradient(ellipse at center,  rgba(255,255,255,1) 0%,rgba(255,255,255,0) 100%); /* W3C */
                filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#00ffffff',GradientType=1 ); /* IE6-9 fallback on horizontal gradient */
            }

            .nav-wrapper {
                background: -moz-linear-gradient(45deg,  rgba(255,255,255,1) 0%, rgba(255,255,255,0.5) 100%); /* FF3.6+ */
                background: -webkit-gradient(linear, left bottom, right top, color-stop(0%,rgba(255,255,255,1)), color-stop(100%,rgba(255,255,255,0.5))); /* Chrome,Safari4+ */
                background: -webkit-linear-gradient(45deg,  rgba(255,255,255,1) 0%,rgba(255,255,255,0.5) 100%); /* Chrome10+,Safari5.1+ */
                background: -o-linear-gradient(45deg,  rgba(255,255,255,1) 0%,rgba(255,255,255,0.5) 100%); /* Opera 11.10+ */
                background: -ms-linear-gradient(45deg,  rgba(255,255,255,1) 0%,rgba(255,255,255,0.5) 100%); /* IE10+ */
                background: linear-gradient(45deg,  rgba(255,255,255,1) 0%,rgba(255,255,255,0.5) 100%); /* W3C */
                filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#80ffffff',GradientType=1 ); /* IE6-9 fallback on horizontal gradient */
            }
        </style>
    </head>
    <body>
        <div id="wrap">
            <div class="navbar navbar-inverse navbar-fixed-top">
                <div class="navbar-inner">
                    <div class="container text-center">
                        <div class="pull-right">
                            <?php if($simplified['mode'] == 'live') { ?>
                            <span class="label label-success">LIVE</span>
                            <?php } elseif($simplified['mode'] == 'sandbox') { ?>
                            <span class="label label-important">SANDBOX</span>
                            <?php } elseif($simplified['mode'] == 'invalid') { ?>
                            <span class="label label-warning">INVALID API KEYS</span>
                            <?php } elseif($simplified['mode'] == 'setup') { ?>
                                <a href="https://www.simplify.com/commerce/app#/account/apiKeys" target="_blank">
                                    <span class="label label-inverse">GET API KEYS <i class="icon-circle-arrow-right"></i></span>
                                </a>
                            <?php } ?>
                        </div>
                        <div class="pull-left">
                            <a class="brand" href="#">
                                <?php if(!empty($simplified['title'])) { echo $simplified['title']; } else { echo "Simplified"; } ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container">
                <div id="top">
                    <?php if(!empty($simplified['debug']) && $simplified['mode'] == 'sandbox') { ?>
                        <div class="well well-small" style=" padding: 10px;font-size: 10px; background: #333; color: #000; text-shadow: 0px 1px 0px #000;">
                            <h2 style="color: #fff;"><i class="icon-bug icon-4x" style="color: #000; float: right; margin-top: -40px;"></i> DEBUG <small>Live Exception Handling Console <em>for Developers</em></small></h2>
                            <pre style="background: #000;"><code style="color: #eee;"><?php echo $simplified['debug']; ?></code></pre>
                            <div style="text-align: right; color: #eee;"><small style="font-style: italic;">Enter a live API key to remove debugging information from this application.</small></div>
                        </div>
                    <?php } ?>
                    <div class="row">
                        <div class="span4 offset2">
                            <div id="form">
                                <?php if($simplified['amount']) { ?>
                                <div id="amount">
                                    <div class="text-center">
                                        <h5 style="display: inline;"><div style="display: inline-block; line-height: 40px;"><small style="position: relative; top: -10px; left: -5px;">pay:</small></div></h5><br class="hidden-desktop" />
                                        <h1 style="display: inline;">
                                            $&nbsp;<span id="dollarAmount"><?php echo substr($simplified['amount'], 0, -2) . "." . substr($simplified['amount'], -2); ?></span>
                                        </h1>
                                    </div><br class="hidden-desktop" />
                                    <div class="text-center" id="wide">
                                            <h5 style="display: inline;"><small>to the order of:</small></h5><br class="hidden-desktop" />
                                            <h5 style="display: inline;">
                                                <?php echo $simplified['company']; ?>
                                            </h5>
                                    </div>
                                </div><br/>
                                <?php } ?>
                                <form id="simplify-payment-form" action="" method="POST">
                                    <div id="simplify-response">
                                        <?php if(!empty($simplified['success'])) { ?>
                                            <div class="alert alert-success" style="font-size: 10px;">
                                                <span class="label label-success"><i class="icon-check" style="color: #fff;"></i> <strong>Thanks!</strong></span> <?php echo $simplified['success']; ?>
                                            </div>
                                        <?php } elseif(!empty($simplified['error'])) { ?>
                                            <div class="alert alert-error" style="font-size: 10px;">
                                                <span class="label label-important"><i class="icon-warning-sign" style="color: #fff;"></i> <strong>Sorry!</strong></span> <?php echo $simplified['error']; ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <div class="row-fluid">
                                        <div class="span9 mobile-three">
                                            <input class="input-block-level" id="cc-number" type="tel" maxlength="20" autocomplete="off" placeholder="Card Number" value="" autofocus />
                                        </div>
                                        <div class="span3 mobile-one">
                                            <input class="input-block-level text-center" id="cc-cvc" type="tel" maxlength="3" autocomplete="off" placeholder="CVC" value=""/>
                                        </div>
                                    </div>
                                    <div class="row-fluid">
                                        <div class="span7 mobile-two">
                                            <select class="input-block-level" id="cc-exp-month">
                                                <option value="01">January</option>
                                                <option value="02">February</option>
                                                <option value="03">March</option>
                                                <option value="04">April</option>
                                                <option value="05">May</option>
                                                <option value="06">June</option>
                                                <option value="07">July</option>
                                                <option value="08">August</option>
                                                <option value="09">September</option>
                                                <option value="10">October</option>
                                                <option value="11">November</option>
                                                <option value="12">December</option>
                                            </select>
                                        </div>
                                        <div class="span5 mobile-two">
                                            <select class="input-block-level" id="cc-exp-year">
                                                <option value="13">2013</option>
                                                <option value="14">2014</option>
                                                <option value="15">2015</option>
                                                <option value="16">2016</option>
                                                <option value="17">2017</option>
                                                <option value="18">2018</option>
                                                <option value="19">2019</option>
                                                <option value="20">2020</option>
                                                <option value="21">2021</option>
                                                <option value="22">2022</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row-fluid">
                                        <div class="span12">
                                            <input class="input-block-level" id="description" name="description" type="text" maxlength="140" autocomplete="off" placeholder="Add a short description..." style="background: #EEE;" value="" />
                                        </div>
                                    </div>
                                    <input id="amountInt" name="amountInt" type="hidden" maxlength="140" autocomplete="off" value="<?php echo intval($simplified['amount']); ?>" />
                                    <button class="input-block-level btn btn-medium" id="process-payment-btn" type="submit"><i class="icon-credit-card"></i> Pay</button>
                                </form>
                                <div class="brand-marks"></div>
                            </div><!-- /form -->
                        </div>
                        <div class="span4 offset1">
                            <div id="intro">
                                <h4>Welcome to <?php if(!empty($simplified['title'])) { echo ucfirst($simplified['title']); } else { echo "Simplified"; } ?>!</h4>
                                <p class="lead">
                                    <?php
                                    if(!empty($simplified['description'])) {
                                        echo $simplified['description'];
                                    } else {
                                        echo 'This is an example application to help get you started accepting payments with Simplify<span class="hidden-phone"> Commerce by MasterCard</span>.';
                                    }
                                    ?>
                                </p>
                                <?php if(empty($simplified['description'])) { ?>
                                <hr class="hidden-tablet hidden-phone" />
                                <p class="hidden-tablet hidden-phone muted">Don't mistake this for the real thing, its yours to build upon! Begin by processing your first payment, then refer to the additional resources to customize your application.</p>
                                <?php } ?>
                            </div><!-- /intro -->
                        </div>
                    </div>
                </div><!-- /top -->
                <?php if(empty($simplified['description'])) { ?>
                <div class="tabs">
                    <div class="nav-wrapper">
                        <ul class="nav nav-tabs">
                            <li<?php if($simplified['mode'] == 'setup') { echo ' class="active"'; } ?>>
                                <a href="#gettingStarted" data-toggle="tab">
                                    <i class="icon-hand-right"></i>
                                    <span class="hidden-phone">Getting Started</span>
                                </a>
                            </li>
                            <li<?php if($simplified['mode'] != 'setup') { echo ' class="active"'; } ?>>
                                <a href="#testing" data-toggle="tab">
                                    <i class="icon-bug"></i>
                                    <span class="hidden-phone">Interactive Testing</span>
                                </a>
                            </li>
                            <li>
                                <a href="#classReference" data-toggle="tab">
                                    <i class="icon-book"></i>
                                    <span class="hidden-phone">Class References</span>
                                </a>
                            </li>
                            <li>
                                <a href="#resources" data-toggle="tab">
                                    <i class="icon-share-alt"></i>
                                    <span class="hidden-phone">Resources</span>
                                </a>
                            </li>
                        </ul>
                    </div> <!-- /nav-wrapper -->
                    <div class="tab-content">
                        <div id="gettingStarted" class="tab-pane<?php if($simplified['mode'] == 'setup') { echo ' active'; } ?>">
                            <div id="gettingStartedWrapper">
                                <ol>
                                    <li>
                                        <p class="lead">Copy your API keys from <a href="https://www.simplify.com/commerce/app#/account/apiKeys" target="_blank">Simplify.com <i class="icon-external-link"></i></a></p>
                                        <small>Make sure you use your <em>sandbox</em> keys!</small><br/><br/>
                                    </li>
                                    <li>
                                        <p class="lead">Open index.php in any text editor.</p>
                                    </li>
                                    <li>
                                        <p class="lead">Paste the keys between the quotes:</p>
                                        <pre class="prettyprint" style="font-size: 10px;"><span class="tag">&lt;?php</span><br/>$simplified = array(<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'publicKey' => 'YOUR_PUBLIC_KEY',<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'privateKey' => 'YOUR_PRIVATE_KEY'<br/>);<br/>// That's it, Simplify, simplified.</pre>
                                        <div class="alert alert-info">
                                            <strong>Tip:</strong> Add #9999 to the end of the URL to make even more money! <a href="#9999" style="color: #3A87AD; text-decoration: underline;">Dont be shy - give it a try <i class="icon-circle-arrow-right" style="color: #3A87AD;"></i></a><br/><small>(...not really, but you can certainly charge more!)</small>
                                        </div>
                                    </li>
                                </ol>
                            </div><!-- /gettingStarted -->
                        </div><!-- /gettingStarted -->
                        <div id="testing" class="tab-pane<?php if($simplified['mode'] != 'setup') { echo ' active'; } ?>">
                            <div class="row-fluid">
                                <div class="span5">
                                    <a href="#"><h4>Success Codes</h4></a>
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th class="api-title-bar">Card Number</th>
                                                <th class="api-title-bar">Response</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td class="card_number">5555555555554444</td><td>MasterCard</td></tr><tr><td class="card_number">5105105105105100</td><td>MasterCard</td></tr><tr><td class="card_number">4012888888881881</td><td>Visa</td></tr><tr><td class="card_number">4111111111111111</td><td>Visa</td></tr><tr><td class="card_number">4222222222222</td><td>Visa</td></tr><tr><td class="card_number">371449635398431</td><td>Amex</td></tr><tr><td class="card_number">378282246310005</td><td>Amex</td></tr><tr><td class="card_number">6011111111111117</td><td>Discover</td></tr><tr><td class="card_number">6011000990139424</td><td>Discover</td></tr><tr><td class="card_number">38520000023237</td><td>Diners</td></tr><tr><td class="card_number">30569309025904</td><td>Diners</td></tr><tr><td class="card_number">3530111333300000</td><td>JCB</td></tr><tr><td class="card_number">3566002020360505</td><td>JCB</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="span7">
                                    <a href="#"><h4>Error Codes</h4></a>
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                        <tr>
                                            <th class="api-title-bar">Card Number</th>
                                            <th class="api-title-bar">Expected Error</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr><td class="card_number">5555555555558726</td><td>Fail with error code: <code class="code-no-block">card.invalid</code></td></tr><tr><td class="card_number">5555555555558742</td><td>Fail with error code: <code class="code-no-block">card.expired</code></td></tr><tr><td class="card_number">5555555555550145</td><td>Fail with a general error: <code class="code-no-block">system</code></td></tr>
                                        </tbody>
                                    </table>
                                    <a href="#"><h4>Status Codes</h4></a>
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                        <tr>
                                            <th class="api-title-bar">Card Number</th>
                                            <th class="api-title-bar">Expected Error</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr><td class="card_number">5555555555557462</td><td>Returns a status of: <code class="code-no-block">DECLINED</code></td></tr><tr><td class="card_number">5555555555554444</td><td>Returns a status of: <code class="code-no-block">APPROVED</code></td></tr>
                                        </tbody>
                                    </table>
                                    <div class="alert alert-info">
                                        <strong>Tip:</strong> Click a card number to test the use case, it will appear for you to process in the form above!
                                    </div>
                                </div>
                            </div>
                        </div><!-- /testing -->
                        <div id="classReference" class="tab-pane">
                            <div class="row-fluid">       
                                <div class="span4">
                                    <a href="https://simplify.com/commerce/docs/apidoc/cardToken">
                                        <i class="icon-credit-card icon-2x icon-large icon-large"></i>
                                        <h4>CardToken</h4>
                                    </a>
                                    <p>One time use token representing card details that can be used to create a payment.</p>
                                </div>
                                <hr class="hidden-middesk" />
                                <div class="span4">
                                    <a href="https://simplify.com/commerce/docs/apidoc/chargeback">
                                        <i class="icon-legal icon-2x icon-large"></i>
                                        <h4>Chargeback</h4>
                                    </a>
                                    <p>An instance of a credit/debit card payment that is in dispute.</p>
                                </div>
                                <hr class="hidden-middesk" />
                                <div class="span4">
                                    <a href="https://simplify.com/commerce/docs/apidoc/coupon">
                                        <i class="icon-tag icon-2x icon-large"></i>
                                        <h4>Coupon</h4>
                                    </a>
                                    <p>Coupons can be applied to subscriptions.</p>
                                </div>
                            </div>
                            <hr/>
                            <div class="row-fluid">
                                <div class="span4">
                                    <a href="https://simplify.com/commerce/docs/apidoc/customer">
                                        <i class="icon-user icon-2x icon-large"></i>
                                        <h4>Customer</h4>
                                    </a>
                                    <p>Customers can be assigned subscriptions for recurring payments.</p>
                                </div>
                                <hr class="hidden-middesk" />
                                <div class="span4">
                                    <a href="https://simplify.com/commerce/docs/apidoc/deposit">
                                        <i class="icon-money icon-2x icon-large"></i>
                                        <h4>Deposit</h4>
                                    </a>
                                    <p>Deposits represent transfers of funds to your bank account.</p>
                                </div>
                                <hr class="hidden-middesk" />
                                <div class="span4">
                                    <a href="https://simplify.com/commerce/docs/apidoc/invoice">
                                        <i class="icon-file-text icon-2x icon-large"></i>
                                        <h4>Invoice</h4>
                                    </a>
                                    <p>An invoice that contains items to charge a customer for subscription(s).</p>
                                </div>
                            </div>
                            <hr/>
                            <div class="row-fluid">
                                <div class="span4">
                                    <a href="https://simplify.com/commerce/docs/apidoc/invoiceItem">
                                        <i class="icon-list icon-2x icon-large"></i>
                                        <h4>InvoiceItem</h4>
                                    </a>
                                    <p>Line items for invoices.</p>
                                </div>
                                <hr class="hidden-middesk" />
                                <div class="span4">
                                    <a href="https://simplify.com/commerce/docs/apidoc/payment">
                                        <i class="icon-dollar icon-2x icon-large"></i>
                                        <h4>Payment</h4>
                                    </a>
                                    <p>An instance of a credit/debit card payment.</p>
                                </div>
                                <hr class="hidden-middesk" />
                                <div class="span4">
                                    <a href="https://simplify.com/commerce/docs/apidoc/plan">
                                        <i class="icon-sitemap icon-2x icon-large"></i>
                                        <h4>Plan</h4>
                                    </a>
                                    <p>Recurring payment plans that are used to create subscriptions.</p>
                                </div>
                            </div>
                            <hr/>
                            <div class="row-fluid">
                                <div class="span4">
                                    <a href="https://simplify.com/commerce/docs/apidoc/refund">
                                        <i class="icon-rotate-left icon-2x icon-large"></i>
                                        <h4>Refund</h4>
                                    </a>
                                    <p>A refund of a previous payment (full or partial).</p>
                                </div>
                                <hr class="hidden-middesk" />
                                <div class="span4">
                                    <a href="https://simplify.com/commerce/docs/apidoc/subscription">
                                        <i class="icon-calendar icon-2x icon-large"></i>
                                        <h4>Subscription</h4>
                                    </a>
                                    <p>Subscriptions to recurring plans for a particular customer.</p>
                                </div>
                                <hr class="hidden-middesk" />
                                <div class="span4">
                                    <a href="https://simplify.com/commerce/docs/apidoc/webhook">
                                        <i class="icon-exchange icon-2x icon-large"></i>
                                        <h4>Webhook</h4>
                                    </a>
                                    <p>Objects representing HTTP endpoints that will receive callbacks after various events.</p>
                                </div>
                            </div>
                        </div><!-- /classReference -->
                        <div id="resources" class="tab-pane">
                            <div class="row-fluid">
                                <div class="span3">
                                    <a href="https://www.simplify.com/commerce/docs/api/index">
                                        <i class="icon-copy icon-4x icon-large"></i>
                                        <h4>API Docs</h4>
                                    </a>
                                    <p>Our simple, straightforward API gets you up and running quickly.</p>
                                </div>
                                <hr class="hidden-middesk" />
                                <div class="span3">
                                    <a href="https://www.simplify.com/commerce/static/api_docs/php/index.html">
                                        <i class="icon-code icon-4x icon-large"></i>
                                        <h4>PHP SDK Docs</h4>
                                    </a>
                                    <p>In depth report of PHP SDK classes, variables, and their respective methods.</p>
                                </div>
                                <hr class="hidden-middesk" />
                                <div class="span3">
                                    <a href="https://www.simplify.com/commerce/docs/tutorial/index">
                                        <i class="icon-sort-by-attributes icon-4x icon-large"></i>
                                        <h4>Tutorial</h4>
                                    </a>
                                    <p>Work through our tutorial to get a comprehensive understanding of Simplify Commerce.</p>
                                </div>
                                <hr class="hidden-middesk" />
                                <div class="span3">
                                    <a href="https://www.simplify.com/commerce/docs/misc/errors">
                                        <i class="icon-warning-sign icon-4x icon-large"></i>
                                        <h4>Error Handling</h4>
                                    </a>
                                    <p>Know how to handle errors returned from the API</p>
                                </div>
                            </div>
                        </div><!-- /resources -->
                    </div><!-- /tab-content -->
                </div><!-- /tabs -->
                <?php } ?>
            </div><!-- /container -->
            <div id="push"></div>
        </div><!-- /wrap -->
        <div id="footer">
            <div class="container">
                <div class="row-fluid">
                    <div class="span4 offset4 text-center">
                        <?php if(!empty($simplified['company'])) { ?>
                            &copy; <?php echo date('Y'); ?> <?php echo $simplified['company']; ?> &bull;
                        <?php } ?>
                        Powered by <a href="https://simplify.com">Simplify</a>
                    </div>
                </div>
            </div>
        </div><!-- /footer -->

        <!-- EXTERNAL JAVASCRIPT (Loaded last, for decreased response time...) -->
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
        <script type="text/javascript" src="https://www.simplify.com/commerce/v1/simplify.js"></script>
        <script type="text/javascript">
            function simplifyResponseHandler(data) {
                var $response = $("#simplify-response");
                // Check for errors
                if (data.error) {
                    // Remove all previous errors
                    $response.html('');
                    // Show any validation errors
                    if (data.error.code == "validation") {
                        var fieldErrors = data.error.fieldErrors,
                        fieldErrorsLength = fieldErrors.length,
                        errorList = '<div class="alert alert-error" style="font-size: 10px;"><span class="label label-important"><i class="icon-warning-sign" style="color: #fff;"></i> <strong>Sorry!</strong></span>';
                        for (var i = 0; i < fieldErrorsLength; i++) {
                            if(i == 0) {
                                if(fieldErrors[i].field == 'card.number') {
                                    errorList += ' Please check your card number.';
                                } else if(fieldErrors[i].field == 'card.cvc') {
                                    errorList += ' Please check your card\'s CVC code.';
                                } else if(fieldErrors[i].field == 'card.expMonth' || fieldErrors[i].field == 'card.expYear') {
                                    errorList += ' Please check the expiration date.';
                                }
                            }
                        }
                        errorList += "</div>";
                        // Display the error
                        $response.html(errorList);
                    }
                    // Re-enable the submit button
                    $("#process-payment-btn").removeAttr("disabled");
                } else {
                    // The token contains id, last4, and card type
                    var token = data["id"];
                    // Insert the token into the form so it gets submitted to the server
                    $('form').append("<input type='hidden' name='simplifyToken' value='" + token + "' />");
                    // Submit the form to the server
                    $('form').get(0).submit();
                }
            }

            $(document).ready(function() {

                // Client-side URL rereouting for variable amounts
                function hashCheck() {
                    if(location.hash && location.hash != '' && location.hash != '#') {
                        var preDollarAmount = location.hash.substring(1);
                        $("#amountInt").val(parseInt(preDollarAmount));
                        var preDecimalAmount = preDollarAmount.substring(0, preDollarAmount.length-2);
                        var postDecimalAmount = preDollarAmount.substring(preDollarAmount.length-2, preDollarAmount.length);
                        $("#dollarAmount").html(parseInt(preDecimalAmount) + "." + parseInt(postDecimalAmount));
                    }
                }
                hashCheck();
                $(window).on('hashchange', function() {
                    hashCheck();
                });

                $("#simplify-payment-form").on("submit", function() {
                    // Display processing alert box within the response
                    var $response = $("#simplify-response");
                    $response.html('<div class="alert alert-warning" style="font-size: 10px;"><span class="label label-warning"><i class="icon-credit-card" style="color: #fff;"></i> Processing...</span> Just a moment, please.</div>');
                    // Disable the submit button
                    $("#process-payment-btn").attr("disabled", "disabled");
                    // Generate a card token & handle the response
                    SimplifyCommerce.generateToken({
                        key: "<?php echo $simplified['publicKey']; ?>",
                        card: {
                            number: $("#cc-number").val(),
                            cvc: $("#cc-cvc").val(),
                            expMonth: $("#cc-exp-month").val(),
                            expYear: $("#cc-exp-year").val()
                        }
                    }, simplifyResponseHandler);
                    // Prevent the form from submitting
                    return false;
                });
                $(".card_number").css('cursor', 'pointer');
                $(".card_number").on("click", function() {
                    $("#cc-number").val(this.innerHTML);
                });
                $("#cc-number").on("keyup", function() {
                    if(this.value.length == '16') {
                        $(this).blur();
                        $("#cc-cvc").select().focus().click();
                    }
                    return;
                });
                $("#cc-cvc").on("keyup", function() {
                    if(this.value.length == 3) {
                        $(this).blur();
                        $("#cc-exp-month").focus();
                    }
                    return;
                });
                $("#cc-cvc").change(function() {
                    $("#cc-exp-year").focus();
                    return;
                });
                $("#cc-cvc").change(function() {
                    $("#cc-exp-year").blur();
                    return;
                });
            });
        </script>
        <script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>
    </body>
</html>