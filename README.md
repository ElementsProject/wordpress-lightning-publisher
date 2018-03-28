# Lightning Publisher for WordPress

[![MIT license](https://img.shields.io/github/license/elementsproject/wordpress-lightning-publisher.svg)](https://github.com/elementsproject/wordpress-lightning-publisher/blob/master/LICENSE)
[![Pull Requests Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](http://makeapullrequest.com)
[![IRC](https://img.shields.io/badge/chat-on%20freenode-brightgreen.svg)](https://webchat.freenode.net/?channels=lightning-charge)

Lightning Publisher for WordPress allows you to offer previews of your blog posts and require a Lightning Network payment to release the rest.

Powered by :zap: [Lightning Charge](https://github.com/ElementsProject/lightning-charge).

![Publisher demo](https://i.imgur.com/xaFSa4E.gif)

## Installation

1. Setup [Lightning Charge](https://github.com/ElementsProject/lightning-charge).

2. Install the [Lightning Publisher for WordPress](https://wordpress.org/plugins/lightning-publisher/) plugin
   from the WordPress.org plugin directory.

   Alternatively, you can [download wordpress-lightning-publisher.zip](https://github.com/elementsproject/wordpress-lightning-publisher/releases)
   and install it manually.

3. Under the WordPress administration panel, go to `Settings -> Lightning Publisher` to configure your Lightning Charge server URL and API token.

Note that Lightning Publisher uses Lightning Charge's built-in checkout page (as an iframe),
meaning that the Lightning Charge server has to be publicly accessible to users.
If users need to access it using a different URL than the one used for communicating with the API,
set this under "Public URL" in the settings page.

## Usage

Add `[ifpaid AMOUNT CURRENCY]` in the place that marks the beginning of paid access to the post. All text prior to that point will be available as a preview to everyone, while all text after that point will only be available to patrons.

For example: `[ifpaid 0.0005 USD]` or `[ifpaid 0.00000005 BTC]`. All the currencies on BitcoinAverage are supported. BTC amounts can have up to 11 decimal places (milli-satoshis precision).

![Editor example](https://i.imgur.com/OfFS8XC.png)

You may also specify a custom message and button text, as follows: `[ifpaid 0.005 ILS text="Please pay to continue reading." button="Alright, I'll pay!"]`. This will show up as:

![Custom pay form example](https://i.imgur.com/oPScnCC.png)

Once the user makes the payment, the page will automatically refresh and the access token will be appended to the URL. The user can bookmark this patron-only URL to return to the content later.
The token does not currently ever expire.

This will look something like: `http://some.blog/trusted-third-parties-are-security-holes/?publisher_access=2bgduhk48gkk480sksoowkssggc0wcokwws0c8k8k8s04wc0gs`

Note that anyone with this URL will be able to access the content. There are currently no restrictions in place to prevent links from being shared.

## License

MIT
