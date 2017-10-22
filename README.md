# Catchjs-php
Catch js is php plugin used to log javascript errors.
Developers mostly cannot see javascript errors in test and live servers.
Testers and end users will find some bugs, but developers need to replicate scenarios to get that bug again,but if it is a javascript error catchjs will log it 
# Usage
Add `catchjs.php` to the root directory of your project
Then include script like this in your html head

```
<script src="catchjs.php?t=js"></script>
```

### Customize
  You can add `catchjs.php` anywhere in your project
  But if you are not adding it in root directory you need to set full url
  -  Add it any where in your project
  - Open catchjs.php (you can include it in any file in that case open that file)
  - Update script url to your url (`http://www.example.com/yourpath/catchjs_included_file`)
  - Add script to head  with new path ie
  
```
<script src="http://www.example.com/yourpath/catchjs_included_file?t=js"></script>
```

ie Where ever you are including add  `?t=js` to get script 

### View Bugs

Just open catchjs.php or included file to view bugs

##### Note
Delete option wont work in old browswes
