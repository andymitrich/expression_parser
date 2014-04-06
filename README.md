###Parser of expressions

This is the parser of special expressions.

Example of expression:

* "model*func_0,func_1('parameter_1','parameter_2')"

* "model*func_0,функция(),include('variable'),!exclude('parameter_1',4)"

* "model*func_0('variable',!include(func_1(func_2(3))))"

Output:

Instance of class with filled fields: "data", "status", "comment"

###Requirements:
php: >=5.3