DESCRIBE system_settings
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
bigint(20)
NO
PRI
NULL
setting_key
varchar(255)
NO
MUL
NULL
setting_value
text
YES
NULL
setting_type
enum('text','number','boolean','json','file','emai...
YES
text
category
varchar(100)
NO
MUL
NULL
description
text
YES
NULL
is_public
tinyint(1)
YES
0
is_editable
tinyint(1)
YES
1
created_at
datetime
YES
current_timestamp()
updated_at
datetime
YES
current_timestamp()
on update current_timestamp()

DESCRIBE design_settings
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
bigint(20) unsigned
NO
PRI
NULL
auto_increment
theme_id
bigint(20)
YES
MUL
NULL
setting_key
varchar(100)
NO
NULL
setting_name
varchar(255)
NO
NULL
setting_value
text
YES
NULL
setting_type
enum('text','number','color','image','boolean','se...
YES
text
category
enum('layout','header','footer','sidebar','homepag...
YES
MUL
other
is_active
tinyint(1)
YES
1
sort_order
int(11)
YES
0
created_at
datetime
YES
current_timestamp()
updated_at
datetime
YES
current_timestamp()
on update current_timestamp()


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE color_settings
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
bigint(20) unsigned
NO
PRI
NULL
auto_increment
theme_id
bigint(20)
YES
MUL
NULL
setting_key
varchar(100)
NO
NULL
setting_name
varchar(255)
NO
NULL
color_value
varchar(7)
NO
NULL
category
enum('primary','secondary','accent','background','...
YES
MUL
other
is_active
tinyint(1)
YES
1
sort_order
int(11)
YES
0
created_at
datetime
YES
current_timestamp()
updated_at
datetime
YES
current_timestamp()
on update current_timestamp()


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE card_styles
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
bigint(20) unsigned
NO
PRI
NULL
auto_increment
theme_id
bigint(20)
YES
MUL
NULL
name
varchar(255)
NO
NULL
slug
varchar(255)
NO
NULL
card_type
varchar(50)
NO
MUL
background_color
varchar(7)
YES
#FFFFFF
border_color
varchar(7)
YES
#E0E0E0
border_width
int(11)
YES
1
border_radius
int(11)
YES
8
shadow_style
varchar(100)
YES
none
padding
varchar(50)
YES
16px
hover_effect
enum('none','lift','zoom','shadow','border','brigh...
YES
none
text_align
enum('left','center','right')
YES
left
image_aspect_ratio
varchar(50)
YES
1:1
is_active
tinyint(1)
YES
1
created_at
datetime
YES
current_timestamp()
updated_at
datetime
YES
current_timestamp()
on update current_timestamp()


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE font_settings
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
bigint(20) unsigned
NO
PRI
NULL
auto_increment
theme_id
bigint(20)
YES
MUL
NULL
setting_key
varchar(100)
NO
NULL
setting_name
varchar(255)
NO
NULL
font_family
varchar(255)
NO
NULL
font_size
varchar(50)
YES
NULL
font_weight
varchar(50)
YES
NULL
line_height
varchar(50)
YES
NULL
category
enum('heading','body','button','navigation','other...
YES
other
is_active
tinyint(1)
YES
1
sort_order
int(11)
YES
0
created_at
datetime
YES
current_timestamp()
updated_at
datetime
YES
current_timestamp()
on update current_timestamp()


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE themes
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
Field
Type
Null
Key
Default
Extra
id
bigint(20)
NO
PRI
NULL
name
varchar(255)
NO
NULL
slug
varchar(255)
NO
UNI
NULL
description
text
YES
NULL
thumbnail_url
varchar(500)
YES
NULL
preview_url
varchar(500)
YES
NULL
version
varchar(50)
YES
1.0.0
author
varchar(255)
YES
NULL
is_active
tinyint(1)
YES
MUL
0
is_default
tinyint(1)
YES
0
created_at
datetime
YES
current_timestamp()
updated_at
datetime
YES
current_timestamp()
on update current_timestamp()


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE activity_logs
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
int(11)
NO
PRI
NULL
auto_increment
user_id
int(11)
YES
MUL
NULL
emp_id
varchar(50)
YES
MUL
NULL
activity_type
varchar(50)
YES
MUL
NULL
description
text
YES
NULL
table_name
varchar(50)
YES
MUL
NULL
record_id
int(11)
YES
MUL
NULL
ip_address
varchar(45)
YES
NULL
user_agent
text
YES
NULL
created_at
datetime
YES
MUL
current_timestamp()


Query results operations
DESCRIBE button_styles
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
bigint(20) unsigned
NO
PRI
NULL
auto_increment
theme_id
bigint(20)
YES
MUL
NULL
name
varchar(255)
NO
NULL
slug
varchar(255)
NO
NULL
button_type
enum('primary','secondary','success','danger','war...
NO
MUL
NULL
background_color
varchar(7)
NO
NULL
text_color
varchar(7)
NO
NULL
border_color
varchar(7)
YES
NULL
border_width
int(11)
YES
0
border_radius
int(11)
YES
4
padding
varchar(50)
YES
10px 20px
font_size
varchar(50)
YES
14px
font_weight
varchar(50)
YES
normal
hover_background_color
varchar(7)
YES
NULL
hover_text_color
varchar(7)
YES
NULL
hover_border_color
varchar(7)
YES
NULL
is_active
tinyint(1)
YES
1
created_at
datetime
YES
current_timestamp()
updated_at
datetime
YES
current_timestamp()
on update current_timestamp()
DESCRIBE roles
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
int(11)
NO
PRI
NULL
auto_increment
key_name
varchar(100)
NO
NULL
display_name
varchar(150)
NO
NULL
created_at
datetime
YES
current_timestamp()


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE role_permissions
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
bigint(20)
NO
PRI
NULL
auto_increment
role_id
bigint(20) unsigned
NO
MUL
NULL
permission_id
bigint(20) unsigned
NO
MUL
NULL
created_at
datetime
YES
current_timestamp()


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE resource_permissions
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
int(11)
NO
PRI
NULL
auto_increment
permission_id
bigint(20) unsigned
NO
NULL
role_id
bigint(20) unsigned
NO
NULL
resource_type
varchar(50)
NO
NULL
can_view_all
tinyint(1)
NO
0
can_view_own
tinyint(1)
NO
0
can_view_tenant
tinyint(1)
NO
0
can_create
tinyint(1)
NO
0
can_edit_all
tinyint(1)
NO
0
can_edit_own
tinyint(1)
NO
0
can_delete_all
tinyint(1)
NO
0
can_delete_own
tinyint(1)
NO
0
created_at
datetime
YES
current_timestamp()


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE permissions
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
bigint(20) unsigned
NO
PRI
NULL
auto_increment
key_name
varchar(100)
NO
NULL
display_name
varchar(150)
NO
NULL
description
text
YES
NULL
created_at
datetime
YES
current_timestamp()
updated_at
datetime
YES
current_timestamp()
on update current_timestamp()
is_active
tinyint(1)
YES
1
module
varchar(100)
YES
NULL


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE Departments
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
department_id
int(11)
NO
PRI
NULL
auto_increment
name_en
varchar(150)
NO
NULL
name_ar
varchar(150)
NO
NULL


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE Sections
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
section_id
int(11)
NO
PRI
NULL
auto_increment
name_en
varchar(150)
NO
NULL
name_ar
varchar(150)
NO
NULL
department_id
int(11)
YES
MUL
NULL


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE Divisions
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
Field
Type
Null
Key
Default
Extra
division_id
int(11)
NO
PRI
NULL
auto_increment
name_en
varchar(150)
NO
NULL
name_ar
varchar(150)
NO
NULL
section_id
int(11)
YES
MUL
NULL


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE users
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
int(11)
NO
PRI
NULL
auto_increment
emp_id
varchar(50)
NO
UNI
NULL
username
varchar(50)
NO
UNI
NULL
email
varchar(191)
NO
UNI
NULL
password_hash
varchar(255)
YES
NULL
preferred_language
varchar(8)
YES
MUL
NULL
phone
varchar(45)
YES
NULL
gender
enum('men','women')
YES
NULL
role_id
int(11)
YES
MUL
NULL
profile_image
varchar(255)
YES
NULL
is_active
tinyint(1)
YES
1
created_at
datetime
YES
current_timestamp()
updated_at
datetime
YES
current_timestamp()
on update current_timestamp()
department_id
int(11)
YES
MUL
NULL
section_id
int(11)
YES
MUL
NULL
division_id
int(11)
YES
MUL
NULL

DESCRIBE user_sessions
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
Field
Type
Null
Key
Default
Extra
id
bigint(20)
NO
PRI
NULL
auto_increment
user_id
int(11)
NO
MUL
NULL
token
char(64)
NO
UNI
NULL
user_agent
text
YES
NULL
ip
varchar(45)
YES
NULL
created_at
datetime
YES
current_timestamp()
expires_at
datetime
YES
NULL
revoked
tinyint(1)
YES
0
updated_at
datetime
YES
current_timestamp()
on update current_timestamp()


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE users
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
int(11)
NO
PRI
NULL
auto_increment
emp_id
varchar(50)
NO
UNI
NULL
username
varchar(50)
NO
UNI
NULL
email
varchar(191)
NO
UNI
NULL
password_hash
varchar(255)
YES
NULL
preferred_language
varchar(8)
YES
MUL
NULL
phone
varchar(45)
YES
NULL
role_id
int(11)
YES
MUL
NULL
profile_image
varchar(255)
YES
NULL
is_active
tinyint(1)
YES
1
created_at
datetime
YES
current_timestamp()
updated_at
datetime
YES
current_timestamp()
on update current_timestamp()
department_id
int(11)
YES
MUL
NULL
section_id
int(11)
YES
MUL
NULL
division_id
int(11)
YES
MUL
NULL

Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Open new phpMyAdmin window

Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Open new phpMyAdmin window
DESCRIBE vehicles
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
int(11)
NO
PRI
NULL
auto_increment
vehicle_code
varchar(50)
NO
UNI
NULL
type
varchar(200)
NO
NULL
manufacture_year
int(11)
NO
NULL
emp_id
varchar(50)
YES
NULL
driver_name
varchar(150)
YES
NULL
driver_phone
varchar(20)
YES
NULL
status
enum('operational','maintenance','out_of_service')
YES
operational
department_id
int(11)
YES
MUL
NULL
section_id
int(11)
YES
MUL
NULL
division_id
int(11)
YES
MUL
NULL
vehicle_mode
enum('private','shift')
YES
shift
gender
enum('men','women')
YES
NULL
notes
text
YES
NULL
created_at
timestamp
YES
current_timestamp()
created_by
int(11)
YES
NULL
updated_by
int(11)
YES
NULL
updated_at
datetime
YES
NULL


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE vehicle_maintenance
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
int(11)
NO
PRI
NULL
auto_increment
vehicle_code
varchar(50)
NO
MUL
NULL
visit_date
date
YES
NULL
next_visit_date
date
YES
NULL
maintenance_type
enum('Routine','Emergency','Technical Check','Mech...
YES
NULL
location
varchar(255)
YES
NULL
notes
text
YES
NULL
created_by
varchar(50)
YES
NULL
updated_by
varchar(50)
YES
NULL
created_at
timestamp
YES
current_timestamp()
updated_at
timestamp
YES
current_timestamp()
on update current_timestamp()


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE vehicle_violations
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
int(11)
NO
PRI
NULL
auto_increment
vehicle_id
int(11)
NO
MUL
NULL
vehicle_code
varchar(50)
NO
NULL
violation_datetime
datetime
NO
NULL
violation_amount
decimal(10,2)
NO
NULL
violation_status
enum('unpaid','paid')
YES
unpaid
issued_by_emp_id
varchar(50)
NO
MUL
NULL
paid_by_emp_id
varchar(50)
YES
MUL
NULL
payment_datetime
datetime
YES
NULL
payment_attachment
varchar(255)
YES
NULL
notes
text
YES
NULL
created_at
datetime
YES
current_timestamp()
updated_at
datetime
YES
NULL


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE vehicle_movement_photos
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
Field
Type
Null
Key
Default
Extra
id
int(11)
NO
PRI
NULL
auto_increment
movement_id
int(11)
NO
MUL
NULL
photo_url
varchar(255)
NO
NULL
taken_by
varchar(50)
YES
NULL
created_at
timestamp
YES
current_timestamp()


Query results operations
Print PrintCopy to clipboard Copy to clipboardCreate view Create view

Your SQL query has been executed successfully.
DESCRIBE vehicle_maintenance
[Edit inline] [ Edit ] [ Create PHP code ]
+ Options
Field
Type
Null
Key
Default
Extra
id
int(11)
NO
PRI
NULL
auto_increment
vehicle_code
varchar(50)
NO
MUL
NULL
visit_date
date
YES
NULL
next_visit_date
date
YES
NULL
maintenance_type
enum('Routine','Emergency','Technical Check','Mech...
YES
NULL
location
varchar(255)
YES
NULL
notes
text
YES
NULL
created_by
varchar(50)
YES
NULL
updated_by
varchar(50)
YES
NULL
created_at
timestamp
YES
current_timestamp()
updated_at
timestamp
YES
current_timestamp()
on update current_timestamp()



