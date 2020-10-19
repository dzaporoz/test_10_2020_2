<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <title><?=$title?></title>

    <style>
        body {
            margin: 0;
            padding: 0;
            font: normal small Arial, Helvetica, sans-serif;
            line-height: 1.8em;
            color: #838B91;
        }

        h1, h2, h3, h4, h5, h6 {
            margin: 0;
            padding: 0;
            font-family: Georgia, "Times New Roman", Times, serif;
            font-weight: normal;
            color: #468259;
        }

        h2 {
            padding-left: 20px;
            font-size: 22px;
        }

        h3 {
            margin-bottom: 1em;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: .9em;
            font-weight: bold;
        }

        p, blockquote, ul, ol {
            margin-top: 0;
        }

        blockquote {
            padding: 0 0 0 40px;
            font: italic small Georgia, "Times New Roman", Times, serif;
            line-height: 1.6em;
        }

        a {
            text-decoration: none;
            color: #468259;
        }

        a:hover {
            background: none;
            text-decoration: underline;
        }

        img {
            width: 100%;
            height: auto;
        }

        /* Header */

        #header {
            width: 754px;
            height: 170px;
            margin: 0 auto;
            padding: 13px 0 0 0;
        }

        #header h1 {
            float: left;
            width: 100%;
            height: 110px;
            padding: 50px 100px 0 20px;
            line-height: 32px;
            font-size: 30px;
        }

        #header h2 {
            float: right;
            width: 494px;
            height: 34px;
            padding: 180px 20px 0 0;
            text-transform: lowercase;
            text-align: right;
            letter-spacing: -1px;
            font-size: 22px;
            color: #FFFFFF;
        }

        /* Content */

        #content {
            width: 754px;
            margin: 0 auto;
        }

        /* Posts */

        #posts {
            width: 754px;
            height: 247px;
            margin: 0 auto;
            padding: 13px 0 0 0;
        }

        #posts .post {
            padding-bottom: 30px;
        }

        #posts .story {
            padding: 15px 20px 0 20px;
        }

        #posts .meta {
            padding: 5px 0 0 20px;
        }

        #posts .meta p {
            margin: 0;
            line-height: normal;
            font-size: smaller;
        }

        #posts ul {
        }

        #posts ul li {
        }

        /* Links */

        #links {
            float: left;
            width: 220px;
        }

        #links ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        #links li ul {
            padding: 15px 20px 30px 20px;
        }

        #links li li {
            padding: 3px 0;
        }

        #links li a {
            background: none;
        }

        #links li i {
            font-size: smaller;
        }

        /* Footer */

        #footer {
            padding: 40px 0 60px 0;
        }

        #footer p {
            width: 750px;
            font-family: Georgia, "Times New Roman", Times, serif;
            color: #A6C09B;
        }

        #footer a {
            background: none;
            font-weight: bold;
            color: #A6C09B;
        }

        #legal {
            margin: 0 auto;
            text-align: right;
            font-size: 12px;
        }

        #brand {
            margin: -35px auto 0 auto;
            padding: 10px 0 0 35px;
            letter-spacing: -1px;
            font-size: 24px;
        }
    </style>

</head>
<body>
<?php echo $content; ?>
</body>
</html>