Fisherman Locator
=================
*Find your lozenges around the globe.*

This website helps you finding where to buy the famous [Fisherman's Friends®](https://fishermansfriend.com/) Lozenges.

<a href="https://fisherman-locator.herokuapp.com/index.php" target="_blank">
  <img 
   src="https://user-images.githubusercontent.com/1292230/112669045-e6ce7d80-8e67-11eb-8c53-c0410e1be527.png"
   alt="Screenshot of the website through an IPhone"
   height="450"
   />
</a>

### Aim of the project
The final Aim of the project is to develop a website that allows its users to get informed about which shops sell the Fisherman's Friends (R) Lozenges,
which are the best equipped and allow them to rate the shops and add new 
ones. While this is the final aim, in this scope the requisites where far less ambitious, so the website presents some bare minimum functionalities and should be considered as a proof of concept.

### Structure of the website
The website implements a classic MVC pattern, as asked in the requirements. The structure can be seen in the doxygen documentation attached in the project itself.

### Current level of functionality
In the current state (version 0.9) the website has the following capabilities:

- Signup system: a user can sign up;
- Login system: a registered user can log in;
- Shops reporting: a logged user can add a new shop to the database;
- Shops research: a user can search shops (match by shop name or city);
- Profile view: a logged user can see its profile informatons;
- Shops removal: an admin can remove shops.

### Project requirements

| Requirement | Details |
|---------------------|:-----------------------------------------------------:|
| HTML | *YES* |
| CSS | *YES* |
| PHP | *YES* |
| MySQL | *YES* |
| two roles at least | *Yes: admin, registered user and not registered user* |
| transactions | *Yes, ShopModel::removeShop()* |
| Ajax | *Yes: BasePageController::loadPageAjaxSearchShop(), Presenter::json()* |

