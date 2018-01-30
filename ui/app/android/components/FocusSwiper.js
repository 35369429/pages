import React, { Component } from 'react';
import { View, Text, Image } from 'react-native';
import Swiper from 'react-native-swiper';
import { color, style, css } from 'skin/components/FocusSwiper';

export default class FocuseSwiper extends React.Component {
    render() {
      let data = this.props.data || [];
      let swiperkey = this.props.swiperkey ;
      if ( data.lengh == 0) {
        return null;
      }
      // console.log( swiperkey );
      // this.forceUpdate();
      return  (
        <Swiper key={swiperkey} style={{height:200}} showsButtons={this.props.showsButtons} >
          { data.map(
            (item, key) => {
               return (
                 <View style={css.slide} key={key}>
                   <Image source={{uri:item.cover}}
                       style={[{width:'100%'},css.wrapper]} />
                 </View>
               )
             }
          )}
         </Swiper>
      )
    }
}
