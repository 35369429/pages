/**
 * 会员中心首页
 */

import React, { Component } from 'react';
import {
    StyleSheet,
    Text,
    Image,
    View
} from 'react-native';

export default class Member extends Component<{}> {
  static navigationOptions = {
    tabBarLabel: '我的',
    // tabBarIcon: ({tintColor, focused})=>{
    //   return (
    //     <Image
    //       source={require('../res/icons/member.png')}
    //       style={[styles.icon, {tintColor: tintColor}]}
    //     />
    //   )
    // }
  };

    render() {
      return (
        <View style={styles.container}>
          <Text style={styles.welcome}>
              会员
          </Text>
        </View>
      );
    }
  }

const styles = StyleSheet.create({

    icon: {
       width: 26,
       height:26,
    },

    container: {
      flex: 1,
      justifyContent: 'center',
      alignItems: 'center',
      backgroundColor: '#F5FCFF',
    },
    welcome: {
      fontSize: 20,
      textAlign: 'center',
      margin: 10,
    },
    instructions: {
      textAlign: 'center',
      color: '#333333',
      marginBottom: 5,
    },
});
