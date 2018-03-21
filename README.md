# Lightning Publisher for WordPress

Lightning Publisher for your WordPress blog allows you to offer previews of your work, then to release the rest with a Lightning Network payment.

Powered by :zap: [Lightning Charge](https://github.com/ElementsProject/lightning-charge).

![Publisher demo](https://i.imgur.com/7uaQ2Ow.gif)

## Installation

1. Setup [Lightning Charge](https://github.com/ElementsProject/lightning-charge).

2. [Download wordpress-lightning-publisher.zip](https://github.com/shesek/wordpress-lightning-publisher/releases)

3. Install and enable the plugin on your WordPress installation.

4. Under the WordPress administration panel, go to `Settings -> Lightning Publisher` to configure your Lightning Charge server URL and API token.


## Usage

Add `[paywall AMOUNT CURRENCY]` in the place that marks the beginning of paid access to the post. All text prior to that point will be available as a preview to everyone, while all text after that point will only be available to patrons.

For example: `[paywall 0.0005 USD]` or `[paywall 0.00000005 BTC]`. All the currencies on BitcoinAverage are supported. BTC amounts can have up to 11 decimal places (milli-satoshis precision).

![Editor example](https://i.imgur.com/sqmE5VL.png)

You may also specify a custom message and button text, as follows: `[paywall 0.005 ILS text="Please pay to continue reading." button="Alright, I'll pay!"]`. This will show up as:

![Custom pay form example](https://i.imgur.com/oPScnCC.png)

Once the user makes the payment, the page will automatically refresh and the access token will be appended to the URL. The user can bookmark this patron-only URL to return to the content later.
The token does not currently ever expire.

This will look something like: `http://some.blog/trusted-third-parties-are-security-holes/?publisher_access=2bgduhk48gkk480sksoowkssggc0wcokwws0c8k8k8s04wc0gs`

Note that anyone with this URL will be able to access the content. There are currently no restrictions in place to prevent links from being shared.

## License

MIT
