<HTML>
    <head>
        <meta property="al:ios:url" content="fayvoapp://itunes.apple.com/us/app/fayvo/id1141717555?mt=8&post_id=<?= $post_id ?>" />
        <meta property="al:android:url" content="fayvoapp://play.google.com/store/apps/details?id=com.fayvo&hl=en&post_id=<?= $post_id ?>">
        <meta property="al:ios:app_store_id" content="apple-app-id" />
        <meta property="al:android:package" content="google-app-package">
        <meta property="al:android:app_name" content="Fayvo">
        <meta property="al:ios:app_name" content="Fayvo" />
        <meta property="og:title" content="example page title" />
        <meta property="og:type" content="website" />
        <title>Redirecting...</title>
        <script> var arr = [];
            var userAgent = navigator.userAgent || navigator.vendor || window.opera;
        </script>
    </head>
    <body>
    <center><h1>Wait...</h1></center>
    <a id="ituneLink" href="https://itunes.apple.com/us/app/fayvo/id1141717555?mt=8&post_id=<?= $post_id ?>" title="iTune Store">App Store</a>
    <br />
    <a id="gPlayLink" href="https://play.google.com/store/apps/details?id=com.fayvo&hl=en&post_id=<?= $post_id ?>" title="iTune Store">Google Play</a>
    <script type="text/javascript">
        function changeLink(applink, defaultURL) {
            setTimeout(function () {
                window.location = defaultURL;
            }, 25);
            window.location = applink;
        }
        if (userAgent.match(/iPad/i) || userAgent.match(/iPhone/i) || userAgent.match(/iPod/i)) {
            var anotherUrl = "https://itunes.apple.com/us/app/fayvo/id1141717555?mt=8&post_id=<?= $post_id ?>";
            changeLink("fayvoapp://itunes.apple.com/us/app/fayvo/id1141717555?mt=8&post_id=<?= $post_id ?>&url=<?=$url?>", anotherUrl);
        } else if (userAgent.match(/Android/i)) {
            var anotherUrl = "https://play.google.com/store/apps/details?id=com.fayvo&hl=en&post_id=<?= $post_id ?>";
            changeLink("fayvoapp://play.google.com/store/apps/details?id=com.fayvo&hl=en&post_id=<?= $post_id ?>&url=<?=$url?>", anotherUrl);
        } else {
            var anotherUrl = "http://fayvo.com/";
            changeLink("http://fayvo.com/", anotherUrl);
        }

    </script>
</body>
</HTML>