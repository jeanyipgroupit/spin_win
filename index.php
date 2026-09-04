<?php
session_start();

/*
|--------------------------------------------------------------------------
| GOOGLE APPS SCRIPT URL
|--------------------------------------------------------------------------
| Replace this with your Google Apps Script Web App URL.
| It should end with /exec
*/
$googleScriptUrl = "https://script.google.com/macros/s/AKfycbwwtdvRECFEQeqvrXWbYLoCKBJQFbG2BcxpyPijVZlsuqe9YIhCCv5awydWBYdBTRw/exec";

/*
|--------------------------------------------------------------------------
| Lucky Draw - Spin The Wheel
|--------------------------------------------------------------------------
| Jean Yip Group
|
| This is a basic working version.
| For production use, connect the form to MySQL and perform the
| prize selection server-side.
|--------------------------------------------------------------------------
*/

$submitted = false;
$error = "";
$winningPrize = null;

/*
|--------------------------------------------------------------------------
| Prize List
|--------------------------------------------------------------------------
*/

$prizes = [
    [
        "id" => 1,
        "title" => "1 X hydrating Treatment",
        "worth" => "Worth $98 - $158"
    ],
    [
        "id" => 2,
        "title" => "1 X Hair Ampoule",
        "worth" => "Worth $58"
    ],
    [
        "id" => 3,
        "title" => "1 X Scalp Detox Treatment",
        "worth" => "Worth $248"
    ],
    [
        "id" => 4,
        "title" => "$50 Service Voucher",
        "worth" => "For all chemical services<br>Based on A-la-carte price"
    ],
    [
        "id" => 5,
        "title" => "1 X Herbal Spa Protection",
        "worth" => "Worth $78"
    ],
    [
        "id" => 6,
        "title" => "1 X Jean Yip Group Product Hamper",
        "worth" => "Worth $688"
    ]
];


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $contact = trim($_POST["contact"] ?? "");

    $staff = trim($_POST["staff"] ?? "");
    $staff_id = trim($_POST["staff_id"] ?? "");
    $package_date = trim($_POST["package_date"] ?? "");
    $package_number = trim($_POST["package_number"] ?? "");
    $amount_collected = trim($_POST["amount_collected"] ?? "");

    $prize_id = intval($_POST["prize_id"] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | Basic Validation
    |--------------------------------------------------------------------------
    */

     if (
        empty($name) ||
        empty($email) ||
        empty($contact) ||
        empty($staff) ||
        empty($staff_id) ||
        empty($package_date) ||
        empty($package_number) ||
        empty($amount_collected) ||
        $prize_id < 1 ||
        $prize_id > count($prizes)
    ) {
        $error = "Please complete all fields.";
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATE EMAIL
    |--------------------------------------------------------------------------
    */
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    }
      /*
    |--------------------------------------------------------------------------
    | GET SELECTED PRIZE
    |--------------------------------------------------------------------------
    */
    else {

        $selectedPrize = null;

        foreach ($prizes as $prize) {

            if ($prize["id"] === $prize_id) {
                $selectedPrize = $prize;
                break;
            }

        }

        if (!$selectedPrize) {

            $error = "Invalid prize selected.";

        } else {

            $winningPrize = $selectedPrize;


            /*
            |--------------------------------------------------------------------------
            | DATA TO GOOGLE SHEET
            |--------------------------------------------------------------------------
            |
            | Google Sheet columns:
            |
            | A = S/N
            | B = LeadDate
            | C = Promotion/Package
            | D = Cost
            | E = Name
            | F = Contact
            | G = Email
            | H = Prize Won
            | I = Saff Name
            | J = Staff ID
            |
            | Additional information:
            | packageDate
            | packageNumber
            |
            */
            $googleData = [

                "leadDate" => date("Y-m-d H:i:s"),

                "promotion" => "Lucky Draw",

                "cost" => $amount_collected,

                "name" => $name,

                "contact" => $contact,

                "email" => $email,

                "prize" => $selectedPrize["title"],

                "staffName" => $staff,

                "staffId" => $staff_id,

                "packageDate" => $package_date,

                "packageNumber" => $package_number

            ];


            $jsonData = json_encode($googleData);

            $ch = curl_init($googleScriptUrl);

            curl_setopt($ch, CURLOPT_POST, true);

            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json"
            ]);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // IMPORTANT: Do NOT follow Google's redirect
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);

            $curlError = curl_error($ch);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);


            if ($curlError) {

                $error = "cURL ERROR: " . $curlError;

            } elseif ($httpCode >= 200 && $httpCode < 400) {

                // Request was accepted by Google Apps Script
                $submitted = true;

            } else {

                $error =
                    "Google Sheet Error. HTTP Code: " .
                    $httpCode .
                    "<br><br>Response:<br>" .
                    htmlspecialchars($response);
            }

        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Jean Yip Group - Lucky Draw</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f8f5f0;
            color: #222;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: auto;
            padding: 40px 20px 60px;
        }

        /* HEADER */

        .header {
            text-align: center;
            margin-bottom: 35px;
        }

        .header h1 {
            font-size: 48px;
            letter-spacing: 2px;
            color: #8d0000;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .header h2 {
            font-size: 25px;
            font-weight: 500;
            color: #222;
        }

        .header .highlight {
            display: inline-block;
            margin-top: 18px;
            padding: 12px 25px;
            background: #8d0000;
            color: #fff;
            font-size: 20px;
            font-weight: bold;
            border-radius: 5px;
        }

        /* WHEEL AREA */

        .wheel-section {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            margin-top: 20px;
        }

        .wheel-wrapper {
            position: relative;
            width: 560px;
            height: 560px;
            max-width: 90vw;
            max-height: 90vw;
        }

        canvas {
            width: 100%;
            height: 100%;
            display: block;
        }

        /* POINTER */

        .pointer {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;

            border-left: 25px solid transparent;
            border-right: 25px solid transparent;
            border-top: 55px solid #111;

            z-index: 10;
        }

        .pointer::after {
            content: "";
            position: absolute;
            top: -55px;
            left: -15px;

            width: 30px;
            height: 30px;

            background: #111;
            border-radius: 50%;
        }

        /* SPIN BUTTON */

        .spin-button {
            margin-top: 35px;
            padding: 18px 60px;

            background: #8d0000;
            color: white;

            border: none;
            border-radius: 50px;

            font-size: 22px;
            font-weight: bold;

            cursor: pointer;

            box-shadow: 0 8px 20px rgba(0,0,0,0.18);

            transition: all 0.2s ease;
        }

        .spin-button:hover {
            background: #b00000;
            transform: translateY(-2px);
        }

        .spin-button:disabled {
            background: #999;
            cursor: not-allowed;
            transform: none;
        }

        /* RESULT */

        .result {
            display: none;

            max-width: 650px;
            margin: 45px auto 0;

            padding: 30px;

            text-align: center;

            background: white;

            border: 2px solid #8d0000;
            border-radius: 15px;

            box-shadow: 0 10px 30px rgba(0,0,0,0.10);
        }

        .result.show {
            display: block;
        }

        .result h2 {
            color: #8d0000;
            font-size: 32px;
            margin-bottom: 15px;
        }

        .result .winner {
            font-size: 25px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .result .worth {
            color: #666;
            line-height: 1.6;
        }

        /* FORM */

        .form-section {
            display: none;

            max-width: 750px;
            margin: 40px auto 0;

            padding: 35px;

            background: white;

            border-radius: 15px;

            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .form-section.show {
            display: block;
        }

        .form-section h2 {
            color: #8d0000;
            text-align: center;
            margin-bottom: 10px;
            font-size: 30px;
        }

        .form-section .form-intro {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;

            font-weight: bold;
            color: #333;
        }

        .form-group input {
            width: 100%;

            padding: 14px 15px;

            border: 1px solid #ccc;
            border-radius: 7px;

            font-size: 16px;

            outline: none;
        }

        .form-group input:focus {
            border-color: #8d0000;
        }

        .section-title {
            margin: 30px 0 20px;

            padding-bottom: 10px;

            border-bottom: 1px solid #ddd;

            color: #8d0000;

            font-size: 20px;
        }

        .submit-button {
            width: 100%;

            margin-top: 15px;

            padding: 16px;

            background: #8d0000;
            color: #fff;

            border: none;
            border-radius: 7px;

            font-size: 18px;
            font-weight: bold;

            cursor: pointer;
        }

        .submit-button:hover {
            background: #b00000;
        }

        /* SUCCESS */

        .success {
            max-width: 700px;

            margin: 50px auto;

            padding: 40px;

            text-align: center;

            background: white;

            border-radius: 15px;

            border: 2px solid #8d0000;
        }

        .success h2 {
            color: #8d0000;
            font-size: 32px;
            margin-bottom: 15px;
        }

        .success p {
            font-size: 18px;
            line-height: 1.7;
        }

        /* ERROR */

        .error {
            max-width: 700px;

            margin: 20px auto;

            padding: 15px;

            background: #ffe5e5;

            color: #900;

            border: 1px solid #c00;

            border-radius: 7px;

            text-align: center;
        }

        /* MOBILE */

        @media (max-width: 700px) {

            .container {
                padding: 25px 15px 50px;
            }

            .header h1 {
                font-size: 32px;
            }

            .header h2 {
                font-size: 19px;
            }

            .header .highlight {
                font-size: 16px;
                padding: 10px 15px;
            }

            .wheel-wrapper {
                width: 95vw;
                height: 95vw;
            }

            .spin-button {
                width: 90%;
                padding: 16px;
                font-size: 20px;
            }

            .form-section {
                padding: 25px 20px;
            }

            .result h2 {
                font-size: 26px;
            }

            .result .winner {
                font-size: 21px;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <?php if ($submitted): ?>

        <div class="success">

            <h2>Thank You!</h2>

            <p>
                The lucky draw entry has been successfully recorded.
            </p>

            <p style="margin-top:15px;">
                <strong>
                    Prize:
                    <?= htmlspecialchars($selectedPrize["title"]) ?>
                </strong>
            </p>

        </div>

    <?php else: ?>

        <!-- HEADER -->

        <div class="header">

            <h1>Spin The Wheel</h1>

            <h2>
                Spend a minimum of <strong>$800</strong>
                and get <strong>1 chance</strong>
            </h2>

            <div class="highlight">
                EVERY SPIN IS A SURE WIN!
            </div>

        </div>


        <?php if ($error): ?>

            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <!-- WHEEL -->

        <div class="wheel-section">

            <div class="wheel-wrapper">

                <div class="pointer"></div>

                <canvas id="wheelCanvas"
                        width="560"
                        height="560">
                </canvas>

            </div>

            <button
                type="button"
                id="spinButton"
                class="spin-button">

                SPIN & WIN

            </button>

        </div>


        <!-- RESULT -->

        <div id="result"
             class="result">

            <h2>Congratulations!</h2>

            <div id="winner"
                 class="winner">
            </div>

            <div id="worth"
                 class="worth">
            </div>

        </div>


        <!-- FORM -->

        <div id="formSection"
             class="form-section">

            <h2>Claim Your Prize</h2>

            <p class="form-intro">
                Please fill in the details below.
            </p>

            <form method="POST"
                  action=""
                  id="claimForm">

                <!-- CUSTOMER DETAILS -->

                <div class="section-title">
                    Customer Details
                </div>

                <div class="form-group">

                    <label for="name">
                        Name *
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        required
                        autocomplete="name">

                </div>


                <div class="form-group">

                    <label for="email">
                        Email *
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        autocomplete="email">

                </div>


                <div class="form-group">

                    <label for="contact">
                        Contact Number *
                    </label>

                    <input
                        type="tel"
                        id="contact"
                        name="contact"
                        required
                        autocomplete="tel">

                </div>


                <!-- STAFF DETAILS -->

                <div class="section-title">
                    Staff To Fill In
                </div>

                <div class="form-group">

                    <label for="staff">
                        Staff Name *
                    </label>

                    <input
                        type="text"
                        id="staff"
                        name="staff"
                        required>

                </div>
                  <div class="form-group">

                    <label for="staff_id">
                        Staff ID *
                    </label>

                    <input
                        type="text"
                        id="staff_id"
                        name="staff_id"
                        required>

                </div>

                <div class="form-group">

                    <label for="package_date">
                        Date of Package Signed *
                    </label>

                    <input
                        type="date"
                        id="package_date"
                        name="package_date"
                        required>

                </div>


                <div class="form-group">

                    <label for="package_number">
                        Package Number *
                    </label>

                    <input
                        type="text"
                        id="package_number"
                        name="package_number"
                        required>

                </div>


                <div class="form-group">

                    <label for="amount_collected">
                        Amount Collected *
                    </label>

                    <input
                        type="number"
                        id="amount_collected"
                        name="amount_collected"
                        min="0"
                        step="0.01"
                        required>

                </div>  
                <!-- HIDDEN PRIZE -->

                <input
                    type="hidden"
                    name="prize_id"
                    id="prize_id">


                <button
                    type="submit"
                    class="submit-button">

                    SUBMIT & CLAIM PRIZE

                </button>

            </form>

        </div>

    <?php endif; ?>

</div>


<script>

/*
|--------------------------------------------------------------------------
| PRIZES
|--------------------------------------------------------------------------
*/

const prizes = [
    {
        title: "1 X hydrating Treatment",
        worth: "Worth $98 - $158"
    },
    {
        title: "1 X Hair Ampoule",
        worth: "Worth $58"
    },
    {
        title: "1 X Scalp Detox Treatment",
        worth: "Worth $248"
    },
    {
        title: "$50 Service Voucher",
        worth: "For all chemical services - Based on A-la-carte price"
    },
    {
        title: "1 X Herbal Spa Protection",
        worth: "Worth $78"
    },
    {
        title: "1 X Jean Yip Group Product Hamper",
        worth: "Worth $688"
    }
];


/*
|--------------------------------------------------------------------------
| CANVAS
|--------------------------------------------------------------------------
*/

const canvas = document.getElementById("wheelCanvas");
const ctx = canvas.getContext("2d");

const size = canvas.width;
const center = size / 2;

const radius = size / 2 - 8;

const segmentAngle =
    (Math.PI * 2) / prizes.length;


/*
|--------------------------------------------------------------------------
| DRAW WHEEL
|--------------------------------------------------------------------------
*/

function drawWheel(rotation = 0) {

    ctx.clearRect(
        0,
        0,
        canvas.width,
        canvas.height
    );

    for (let i = 0; i < prizes.length; i++) {

        const startAngle =
            rotation +
            i * segmentAngle;

        const endAngle =
            startAngle +
            segmentAngle;


        /*
        |--------------------------------------------------------------------------
        | Alternating Red / White
        |--------------------------------------------------------------------------
        */

        ctx.beginPath();

        ctx.moveTo(center, center);

        ctx.arc(
            center,
            center,
            radius,
            startAngle,
            endAngle
        );

        ctx.closePath();

        ctx.fillStyle =
            i % 2 === 0
                ? "#a40000"
                : "#ffffff";

        ctx.fill();

        ctx.lineWidth = 3;
        ctx.strokeStyle = "#8d0000";

        ctx.stroke();


        /*
        |--------------------------------------------------------------------------
        | Text
        |--------------------------------------------------------------------------
        */

        ctx.save();

        ctx.translate(center, center);

        ctx.rotate(
            startAngle +
            segmentAngle / 2
        );

        ctx.textAlign = "right";

        if (i % 2 === 0) {
            ctx.fillStyle = "#ffffff";
        } else {
            ctx.fillStyle = "#8d0000";
        }

        ctx.font =
            "bold 16px Arial";

        const lines =
            splitText(
                prizes[i].title,
                27
            );

        let textY = -8;

        lines.forEach(function(line) {

            ctx.fillText(
                line,
                radius - 22,
                textY
            );

            textY += 19;

        });


        ctx.font =
            "12px Arial";

        const worthLines =
            splitText(
                prizes[i].worth,
                32
            );

        textY += 5;

        worthLines.forEach(function(line) {

            ctx.fillText(
                line,
                radius - 22,
                textY
            );

            textY += 15;

        });

        ctx.restore();

    }


    /*
    |--------------------------------------------------------------------------
    | Center Circle
    |--------------------------------------------------------------------------
    */

    ctx.beginPath();

    ctx.arc(
        center,
        center,
        62,
        0,
        Math.PI * 2
    );

    ctx.fillStyle = "#ffffff";

    ctx.fill();

    ctx.lineWidth = 5;

    ctx.strokeStyle = "#8d0000";

    ctx.stroke();


    ctx.fillStyle = "#8d0000";

    ctx.textAlign = "center";

    ctx.textBaseline = "middle";

    ctx.font =
        "bold 18px Arial";

    ctx.fillText(
        "LUCKY",
        center,
        center - 10
    );

    ctx.fillText(
        "DRAW",
        center,
        center + 12
    );

}


/*
|--------------------------------------------------------------------------
| Split Text
|--------------------------------------------------------------------------
*/

function splitText(text, maxLength) {

    const words =
        text.split(" ");

    const lines = [];

    let current = "";

    words.forEach(function(word) {

        if (
            (current + " " + word).trim()
                .length > maxLength
        ) {

            if (current !== "") {
                lines.push(current);
            }

            current = word;

        } else {

            current =
                (current + " " + word).trim();

        }

    });

    if (current !== "") {
        lines.push(current);
    }

    return lines;
}


/*
|--------------------------------------------------------------------------
| Initial Wheel
|--------------------------------------------------------------------------
*/

drawWheel();


/*
|--------------------------------------------------------------------------
| SPIN
|--------------------------------------------------------------------------
*/

const spinButton =
    document.getElementById("spinButton");

let currentRotation = 0;

let spinning = false;


spinButton.addEventListener(
    "click",
    function() {

        if (spinning) {
            return;
        }

        spinning = true;

        spinButton.disabled = true;

        /*
        |--------------------------------------------------------------------------
        | Random winning prize
        |--------------------------------------------------------------------------
        */

        const winnerIndex =
            Math.floor(
                Math.random() * prizes.length
            );


        /*
        |--------------------------------------------------------------------------
        | Calculate rotation
        |--------------------------------------------------------------------------
        */

        const segmentCenter =
            winnerIndex * segmentAngle +
            segmentAngle / 2;


        const pointerAngle =
            -Math.PI / 2;


        const targetRotation =
            pointerAngle -
            segmentCenter;


        /*
        |--------------------------------------------------------------------------
        | Add multiple rotations
        |--------------------------------------------------------------------------
        */

        const extraSpins =
            6 * Math.PI * 2;


        const finalRotation =
            targetRotation +
            extraSpins;


        const startRotation =
            currentRotation;


        const duration =
            5500;

        const startTime =
            performance.now();


        /*
        |--------------------------------------------------------------------------
        | Animation
        |--------------------------------------------------------------------------
        */

        function animate(currentTime) {

            const elapsed =
                currentTime - startTime;

            let progress =
                Math.min(
                    elapsed / duration,
                    1
                );


            /*
            |--------------------------------------------------------------------------
            | Ease Out
            |--------------------------------------------------------------------------
            */

            const ease =
                1 -
                Math.pow(
                    1 - progress,
                    4
                );


            currentRotation =
                startRotation +
                (finalRotation - startRotation) *
                ease;


            drawWheel(currentRotation);


            if (progress < 1) {

                requestAnimationFrame(
                    animate
                );

            } else {

                spinning = false;

                showWinner(
                    winnerIndex
                );

            }

        }


        requestAnimationFrame(
            animate
        );

    }
);


/*
|--------------------------------------------------------------------------
| SHOW WINNER
|--------------------------------------------------------------------------
*/

function showWinner(index) {

    const prize =
        prizes[index];


    document.getElementById(
        "winner"
    ).textContent =
        prize.title;


    document.getElementById(
        "worth"
    ).textContent =
        prize.worth;


    document.getElementById(
        "prize_id"
    ).value =
        index + 1;


    document.getElementById(
        "result"
    ).classList.add("show");


    setTimeout(function() {

        document.getElementById(
            "formSection"
        ).classList.add("show");


        document.getElementById(
            "formSection"
        ).scrollIntoView({
            behavior: "smooth"
        });

    }, 700);

}

</script>

</body>
</html>