
## What is this?

This is a library to control SP108E Wi-Fi controller.
For those who don't know what it does. In a nut shell,
It's a controller box to control addressable LED strips.
It can control strips based on 3-wires chips like: 
WS2811, WS2812B(power, ground and data lines), 
Or 4-wires chips (power, ground, data and clock lines).
There's an official app for it for android and IOS,it's called LED shop, but there isn't any for windows, linux or mac; So I made this library. it can do almost everything the official app can do, except for some functions, which are under the To-Do list.

## Let's make this clear!
This is the version 1 of the library so the code 
isn't the pretties, but it's not a big deal, for V1
I want to keep it simple and easy for anyone to know 
what the instructions are, make the library do the same 
functions as the official APP and make it easy to understand.
without worrying too much about the code quality or complicating things.

## Why PHP?

PHP was the perfect choice for getting it up and running so fast.
No compiling, no worring about cross-platform support, And more importantly to make it easy for anyone to use it if he/she is a beginner.

## To-Do:

- Add support for connecting the controller for an exiting Wi-Fi network
- Add support for finding controllers in the current network
- ~~Add support for the playback function that's in the official APP~~ (Needs a better solution)

## How does the APP conrol the SP108E box?

1. It broadcasts a who has request to find the device on the
    network*
 2. It tries to communicat with it by opening a TCP connection on port number 8189  
3. It sends commands to get the box name and its current settings
4. It sends commands to set strip properties like: IC chip type, number of segments, number of LEDs per segment, color  order, device name, etc.. 
5. It It sends commands to change the animation, it's speed, color and different animation's properties; based on what the user has selected.

 \* The network is either your home wifi network, which your mobile and the box are connect to it or the box own wifi network, to which your mobile is connected .
 \* the app can send a command to set the details of your home wifi network

## The structure of the commands
- All commands are 6-bytes long
- All commands first byte is "38" and last byte is "83"
- The 5th byte is the instruction byte, which tells the box what to do
-  If a specific instuction requires a value to be sent with it to the box, the value with will be sent in the 2nd, 3rd and 4th bytes**, And if it doesn't then the 2nd, 3rd and 4th bytes will be garbage bytes***.
- If the value is:
  - 1-byte long, it will be sent in the 2nd byte. And the 3rd and 4th bytes will be garbage bytes
  - 2-bytes long, it will be sent in the 2nd and 3rd bytes. And the 4th bytes will be garbage byte.
  - 3-bytes long, it will be sent in the 2nd, 3rd and 4th bytes.

**Some instrucions require a value but it will be sent in the next request.
***The garbage bytes are bytes, which can have any value and it won't affect how the instruction is excuted or how the box works.

## Device information commands

#### Get device name: 38 xx xx xx 77 83
| Start byte | Garbage bytes |Instruction byte | End byte |
|--|--|--|--|--|
| 38 | xx xx xx | 77 | 83 |
- Response:
  - 18-byes long
  - device name always start with SP108E_
  - Example: SP108E_0123456789

#### Get current device settings: 38 xx xx xx 10 83
| Start byte | Garbage bytes |Instruction byte | End byte |
|--|--|--|--|--|
| 38 | xx xx xx | 10 | 83 |

- Response: 
  - 17-byes long
  - Example: 38 01 b3 79 ff 02 000c 000d ff0000 03 01 ff 83
  
| Start byte | device on/off | current animation| animation speed | LEDs brightness | color order | LEDs per segment | Number of segments | current mono animation color | IC type | number of recorded patterns | white LED brightness (for RGBW strips) | End byte |
|--|--|--|--|--|--|--|--|--|--|--|--|--| 
| 38 | 01| b3| 79| ff | 02 | 000c | 000d | ff0000 | 03 | 01 | ff | 83 |

## Setting device information commands
#### Change mono animation color 
Example: 38 FF0000 2C 83 (sets it to red )
| Start byte | Color |Instruction byte | End byte |
|--|--|--|--|--|
| 38 | FF0000 | 2C | 83 |

#### Change animation speed
Example: 38 96 0000 03 83 (sets it to 150 )
| Start byte | speed | Garbage bytes |Instruction byte | End byte |
|--|--|--|--|--|--|
| 38 | 96 | 00 00 | 03 | 83 |

#### Change LEDs brightness
Example: 38 FF 0000 2A 83 (sets it to 255 )
| Start byte | Brightness | Garbage bytes |Instruction byte | End byte |
|--|--|--|--|--|--|
| 38 | FF | 00 00 | 2A | 83 |

#### Change White LED brightness
Example: 38 FF 0000 08 83 (sets it to 255 )
| Start byte | White LED Brightness | Garbage bytes |Instruction byte | End byte |
|--|--|--|--|--|--|
| 38 | FF | 00 00 | 08 | 83 |

#### Change number of segments
Example: 38 0800 00 2e 83 (sets number of segments to 2048)
| Start byte | number of segments | Garbage byte |Instruction byte | End byte |
|--|--|--|--|--|--|
| 38 | 0800 | 00 | 2e | 83 |

#### Change number of LEDs per segment
Example: 38 0019 00 2d  83 (sets number of LEds per segment to 25)
| Start byte | number of LEDs per segment | Garbage byte |Instruction byte | End byte |
|--|--|--|--|--|--|
| 38 | 0019 | 00 | 2d | 83 |

#### Set chip type
Example: 38 03 0000 1c 83 (sets chip type to WS2811)
| Start byte | Chip type | Garbage byte |Instruction byte | End byte |
|--|--|--|--|--|--|
| 38 | 03 | 0000 | 1c | 83 |

#### Toggle off or on
Example: 38 000000 aa 83
| Start byte | Garbage byte |Instruction byte | End byte |
|--|--|--|--|--|
| 38 | 000000 | aa| 83 |

#### Enable mixed animation auto mode
Example: 38 000000  06 83
| Start byte | Garbage byte |Instruction byte | End byte |
|--|--|--|--|--|
| 38 | 000000 | 06| 83 |

#### Change mixed animation
Example: 38 03 0000 2c 83 (change it to animation number 3)
| Start byte | Animation number | Garbage byte |Instruction byte | End byte |
|--|--|--|--|--|--|
| 38 | 03 | 0000 | 2c | 83 |

#### Change mono color animation
Example: 38 cd 0000 2c 83 (change it to meteor animation )
| Start byte | Mono color animation | Garbage byte |Instruction byte | End byte |
|--|--|--|--|--|--|
| 38 | cd | 0000 | 2c | 83 |

#### Set color order
Example: 38 02 0000 3c 83 (set it to GRB )
| Start byte | Color order | Garbage byte |Instruction byte | End byte |
|--|--|--|--|--|--|
| 38 | 02 | 0000 | 3c | 83 |

#### Set device name
1. Ask it to change the name
Example: 38 000000  14 83
2. Wait for the response

| Start byte | Garbage byte |Instruction byte | End byte |
|--|--|--|--|--|
| 38 | 000000 | 14| 83 |

3. If the response is 1, Send the new name (maximum 10 characters)
 Example: 49636520436f6c64 (set it to Ice Cold)

