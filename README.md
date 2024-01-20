# Challenge

## Code Review

### Backend (`index.php`)

A first glance at the code tells me that there's no coding standard in place. I would suggest using a coding standard
like `PSR-12`. It's also good to decide on a naming convention. I would suggest using `camelCase` for variables and
methods, `PascalCase` for classes and so on. Tools like `PHP_CodeSniffer` or `PHP-CS-Fixer` can help with keeping the
codebase consistent. The code seems to be a new feature and there are **no tests** added to the PR. The file as a whole
is breaking the first SOLID principle which is the Single Responsibility Principle. The file is doing too many
things at once. It's handling the user input, it's making requests to the mock server, it's handling the responses from
the mock server etc. The class `RatenKredit` is also breaking the Open-Closed Principle. It's not open for extension
because it's not possible to add a new provider without modifying the class.

Here are some of my observations and suggestions for improvements going through the code line by line:
* ```php
  declare(strict_types=1);
  ```
  I don't see this statement. It's a good practice to use **strict types**. It helps to avoid some common mistakes, and
  it also helps to improve performance.
* ```php
  class RatenKredit
  ```
  RatenKredit is a German word for installment credit. It depends on the company policies but a general approach and
  common practice is to use English language in a naming convention. The class name in my opinion also doesn't reflect
  the purpose of the class. I would rename it to `class InstallmentLoanOfferProvider` or `class InstallmentLoanService`
  or similar.
* ```php
  public function __construct() {
      ini_set('display_errors', 'off');
  }
  ```
  It's very unusual and probably bad practice to use `ini_set` function inside a constructor. One way to configure
  options like `display_errors` is inside the `php.ini` file. This file is usually configured differently for staging or
  development environment and for production. In production environment you don't want to expose any **raw errors** to
  the end user because of security reasons. The outputted errors might reveal information about the system, the
  infrastructure or even worse, they might expose secret credentials to the public. In development environment you
  usually want to see all errors and warnings, even deprecation notices. If setting the `display_errors` value in
  `php.ini` is not an option then you can at least move the statement to the earliest possible place in the codebase
  (e.g. inside `/public/index.php`).
* ```php
  public function get($providers = null)
  ```
  The method name `get` is not very descriptive. The `$providers` argument also has no type defined. I would rename the
  method to e.g. `getOffers` and I would define the argument type as `?array` or `null|array`.
* ```php
  if (!$providers) {
      $providers = ['ing-diba','Smava',  'ba_fin'];
  } else $providers = [$providers];
  $r = array();
  ```
  One might argue that this code is not very readable or that it can be optimized. I would rewrite it to:
  ```php
  $providers ??= ['ing-diba', 'smava', 'ba-fin'];
  $offers = [];
  ```
  The variable `$r` has a too short name and is not very descriptive. I would rename it to `$offers` or `$loanOffers`.
  Personally I also prefer using `[]` over `array()` because it's shorter and more readable. The default values inside
  `$providers` array also don't look very consistent. I would use either `snake_case` or `kebab-case` but not both.
* ```php
  $ingdiba = "https://api.jsonbin.io/v3/b/65a6e50e266cfc3fde79aa14?meta=false&amount=$_GET[amount]";
  ```
  The *amount* value from superglobal `$_GET` is not sanitized or validated in any way. It's a potential risks for
  system stability or security.
* ```php
  for ($i = 0; $i <= count($providers); $i++) {
  ```
  Here there's a typo or a bug that results in the "blank" row in the table. It should be
  `($i = 0; $i < count($providers); $i++)` because we start counting from 0. We should also be aware of the fact that
  the keys don't necessarily have to be numeric and sequential. So something  like `array_values($providers)` would
  already help, or we could also use a `foreach` loop instead of a `for` loop.
* ```php
  $offer = file_get_contents($ingdiba, false, stream_context_create([
      "http" => [
          "method" => "GET",
          "header" => 'X-Access-key: $2a$10$NH1p52EaThQFAUbsMloZ.ObhsAsdBC77RJROzFiJ7OUc52oBIn5DS' // this is only for mock
      ]
  ]));
  $offer = json_decode($offer, true);
  ```
  Access keys, tokens, credentials, passwords, etc. should never be hardcoded in the source code. It gets even worse
  when the source code is committed to a public repository like GitHub. There can be different tools in place for
  storing credentials like Vault by HashiCorp or AWS Secrets Manager. These credentials would be then retrieved for
  example at pipeline runtime and injected into e.g. the environment variables.
  There is also no error handling in place. Function `json_decode` expects the first argument to be a string but
  `$offer` variable could also be `false` if the `file_get_contents` function fails for whichever reason (network
  issues, endpoint changes, server issues etc.). There could also be an issue
  decoding the JSON string.
* ```php
  $curl = curl_init();
  curl_setopt_array($curl, array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => 'https://api.jsonbin.io/v3/b/65a6e71e1f5677401f1ebd2c?meta=false',
      /*post does not work with mock server CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS => array(
          'month' => 3,
          'loan' => $_GET['amount']
      ),*/
      CURLOPT_HTTPHEADER => [
          'X-access-key: $2a$10$NH1p52EaThQFAUbsMloZ.ObhsAsdBC77RJROzFiJ7OUc52oBIn5DS',
      ]
  ));

  $offer = json_decode(curl_exec($curl), true);
  curl_close($curl);
  ```
  Here we use `curl` instead of `file_get_contents` to make a request to the mock server. The credentials are again
  hardcoded. If the plan was to use a `POST` request then we should sanitize and validate the `$_GET['amount']` value.
  The `month` is set to `3` which is not very flexible. Maybe we always want an offer over the course of 3 months but
  maybe from the business perspective we want to offer a loan over the course of 6 months or 12 months. So maybe in the
  next development iteration we would want to make this value configurable. Again we have no error handling in place.
* ```php
          case 'ba_fin':
          // no api docs yet?
  }
  $r[$providers[$i]] = $offer;
  ```
  This is a case without any logic. I would either add a `@todo` comment or I would remove the case completely. And even
  if there's no logic, the `$r` array still gets populated with value of `$offer` from the previous iteration. That's
  why the last "blank" row still has some data.
* ```php
  if (@$_GET['submit']) {
      $ratenkredit = new RatenKredit();
      $offers = $ratenkredit->get($_ENV['providers']);
  }
  ```
  Muting errors with `@` is a bad practice. It's better to use `isset` or `array_key_exists` to check if the key
  exists in the array. The same goes for `$_ENV['providers']`. The `$_ENV` part is maybe even a typo, and it was
  supposed to be a `$_GET`? The `$_ENV['providers']` tells me that it can be multiple providers, but we would break the
  code doing something like `&providers[]=ing-diba` because of `} else $providers = [$providers];` part. So we would
  have to make some changes. The `$_ENV['providers']` might also not be present, so we should add something like
  `?? null`.

It would not be safe to deploy this code into production. Lack of sanitization and validation of user input and lack of
error handling are the main reasons. There are also potential bugs in the code that could have been found by adding
unit tests. In order to gain performance we could also add some caching mechanism. We could cache the responses from
the mock server for a certain amount of time.

A potential testing approach would be to add unit tests for the `RatenKredit` class. We could also use tools like
Playwright or Cypress to write end-to-end tests. And it's always a good idea to use static code analyzers like PHPStan
or Psalm to pinpoint potential bugs and issues.

### Frontend (`view.phtml`)

It looks like the frontend was done in a hurry. There's internal CSS that could have been extracted to a separate file.
There's no Normalize CSS or Reset CSS in place. The CSS is placed on a very generic selectors, maybe it would be better
to use some classes following BAM methodology instead.

The HTML semantic can be improved with the main HTML5 elements like `<header>`, `<main>`, `<footer>`, `<nav>`, etc.

Here are some other suggestions:
* ```html
  <h2>Ratenkredit</h2>
  ```
  The `<h1>` is either missing or this line should be an `<h1>`. The document is defined as `<html lang="en">` so we
  should stick to English language.
* ```html
  <form>
      <input type="text" name="amount" value="<?=@$_GET['amount'] ?>" placeholder="$100">
      <input type="submit" name="submit" value="Check" />
  </form>
  ```
  Some important `<form>` attributes like the `action` and `method` are missing. Labels are also missing. The first
  input is defined as `type="text"` but it's probably supposed to be a `type="number"` without optional `min` and `max`
  values. In this case we would have to get rid of the `$` sign in the placeholder. Maybe adding the `$` sign before the
  button or before the first input would be a better approach. We could use the `<button>` element instead of
  `<input type="submit">`.
* ```html
  <table border="2">
      <tr>
          <th colspan="4">Offers</th>
      </tr>
  ```
  It should actually be `<th colspan="3">`. The border attribute is obsolete and should be replaced with CSS.
* ```html
      <?php foreach ($offers as $provider => $offer) { ?>
      <tr>
          <td><?= $provider ?></td>
          <td><?php if (($provider) == 'ing-diba') echo $offer['zinsen']; else { echo $offers[$provider]['Interest']; } ?>%</td>
          <td><?php if ($provider == 'ing-diba') echo $offer['duration']; else { echo $offer['Terms']['Duration']; } ?> month</td>
      </tr>
      <?php } ?>
  </body>

  <?php endif ?>
  ```
  There's a missing `</table>` tag and the `<?php endif ?>` should be before the `</body>` tag.
  This part doesn't look very clean. It looks quite clumsy, and it's not very readable. We see that the data coming from
  the mock server is not consistent. The `ing-diba` provider has different keys than the other providers. Not to mention
  the different **units** and **formats**. So we need to be aware of different mock implementations, and we need to
  handle them differently. Maybe it's time to think about an abstraction layer that would handle the data normalization.

## Refactoring

The book Domain-Driven Design in PHP by Carlos Buenosvinos, Christian Soronellas, and Keyvan Akbary would describe the
code in `index.php` as *Big Ball of Mud*. The presentation, application, and domain layers are all mixed together.
From the code maintenance perspective it would be better to separate the code into different layers.

### Layered architecture

Layered architecture is a common pattern used in software design. It's a way to separate concerns and responsibilities
into different layers and to make the code more maintainable and testable. In a four-layered architecture, the layers
are typically divided into:
* Presentation
    * The presentation layer is responsible for presenting the data to the user and for handling user input.
    * This would be the place for our `view.phtml` file.
* Application
    * The application layer is responsible for handling business logic and coordinating interactions between different
      components.
    * This would be the place for a controller class that would handle the user input and would delegate the work to the
      domain layer.
* Domain
    * The domain layer contains the business logic and rules of the application.
    * This would be the place for our `RatenKredit` class.
* Infrastructure
    * The infrastructure layer is responsible for handling external dependencies like databases, APIs, etc.
    * This would be the place for our mock server interactions.

### Assumption

A PR typically gets suggestions for improvements, concerns, opinions, and ideas from other team members. Unfortunately
this PR is in a state that can't be fixed or improved with a few minor changes. So I'm allowing myself to completely
refactor the logic in a more modern way. What I'm not sure about is the *month* parameter. It was mentioned inside the
Smava Curl request. I'm also not so familiar with installment loans, but I guess that the term or duration of the loan
in months can also be negotiable. That's why I added the *months* field to the form, although it doesn't really do
anything.

### Final result

I started by creating a new Symfony 6.4 LTS app and adding the most important `LoanOfferHandler` class. It retrieves 
loan offers from available providers. It's also easy to add new providers. I implemented a simple data normalization 
without any proper endpoint specs at hand. I also added a simple caching mechanism to improve performance. I added a few 
unit tests to cover the most important parts of the code.

To start the app you can simply run:
```shell
> symfony server:start
```

You should browse to `https://localhost:8000/loan-offers` (depends on local settings) to see the app in action.

For running tests you can use:
```shell
> bin/phpunit
```

It is a nice coding challenge and I could definitely spend some more hours on it but I think that the code is already
in a much better shape than in the PR.
