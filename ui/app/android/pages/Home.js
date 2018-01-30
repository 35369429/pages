/**
 * 主页
 */
import React, { Component } from 'react';
import { TabNavigator, StackNavigator } from 'react-navigation';
import {
   StyleSheet,
   SectionList,
   FlatList,
   RefreshControl,
   StatusBar,
   Text,
   Image,
   View,
   TouchableOpacity
} from 'react-native';
import { UltimateListView, UltimateRefreshView } from 'react-native-ultimate-listview';
import mobx, { observable, computed } from "mobx";
import { observer } from 'mobx-react';
import Loader from '../components/Loader';
import ArticleList from './article/List';
import ArticleDetail from './article/Detail';

import {color, css, tabNav} from 'skin/pages/Home';
import xpm from '../xpm';

/**
 * Home 页面数据驱动
 */
class __data {

  @observable cates = [];
  @observable loading = false;
  @observable curr = null;

  constructor() {
     // mobx.autorun(() => console.log('auto run', this.page));
  }

  reset() {
    this.curr  = null;
    this.loading = false;
  }

  setCurrent( curr ) {
    this.curr = curr;
  }

  fetch( key = null ) {

    this.loading = true;
    let $search = xpm.api('/xpmsns/pages/category/search');

    $search().get({perpage:40, page:1, param:'isnav=true', order:'priority asc'}).then(( cates )=>{

      let total = cates.total || 0;

      if ( total == 0 ) {
        // 读取数据失败
        console.log( 'cates excp:',  cates );
        return;
      }

      for ( var idx in cates.data ) {
        cates.data[idx]['key'] = cates.data[idx]['category_id'];
        cates.data[idx]['api'] = cates.data[idx]['category_id']
      }
      // console.log( cates.data );
      this.cates = cates.data;

      if ( key == null ) {
        key = this.cates[0]['key'];
      }
      this.curr = key;
      this.loading  = false;
    }).catch( (excp) => {
      // 读取数据失败
      console.log( 'excp:',  excp );
    });
  }

}

let $data = new __data();

@observer
class NavList extends Component<{}> {

  constructor(props, context) {
    super(props, context);
  }

  componentDidMount() {
      // console.log('\tnavigation', this.props.navigation);
      // $data.fetch('10001');
  }

  render() {


    if ($data.cates.length <= 0 ) {
        return (<Loader />);
    }

    let navs  = {}, routers = {}, pages = {};
    $data.cates.map((item,idx)=>{
      let c = item;
      // console.log('c=', c);
      let key = c['key'];
      navs[key] = {
        screen:()=><ArticleList {...c} curr={key} data={$data} navigate={this.props.navigation.navigate} />,
        navigationOptions:{title:c['name']}
      }
    });

    let NavTabs = TabNavigator(navs, tabNav);
    return (
        <NavTabs

          onNavigationStateChange={
            (prevState, currentState, action )=>{
              let prev = prevState.routes[prevState.index]['key'];
              let curr = currentState.routes[currentState.index]['key'];
              $data.setCurrent(curr);
            }
          }/>
     );
   }
}


/**
 * 页面
 */
const PageApp = StackNavigator({
  Home: {
    screen:NavList,
    navigationOptions: {
      header: () => { return (
        <View style={css.header} >
          <Image
            source={require('../skin/default/res/icons/logo.png')}
            style={[css.logo, {tintColor: color.light}]}
          />
          <Text style={css.text}> XpmSNS-头条 (演示版) </Text>
        </View>
      );}
    },
  },
  Detail:{
    path:'article/:id',
    screen:ArticleDetail,
    navigationOptions:{
      header: (page) => {
        // let params = page.navigation.state[1].params || {}
        let params = {};
        let idx =  page.navigation.state.index;
        params = page.navigation.state.routes[idx].params || {};

        return (
          <View style={[css.header,css.title]} >
              <Text style={css.text}> {params.title} </Text>
              <TouchableOpacity onPress={()=>{
                console.log( page.navigation.goBack );
                page.navigation.goBack();}}>
                <Image
                  source={require('../skin/default/res/icons/return.png')}
                  style={[css.btn, {tintColor: color.light}]}
                />
              </TouchableOpacity>
          </View>
      )}
    }
  }
}, {
  headerMode:'screen',
  cardStyle:{backgroundColor:color.white}
})


export default class Home extends Component<{}> {

  componentDidMount() {
      // console.log('page loaded');
      $data.fetch();
  }
  render() {
    return (
        // <ArticleList  category_id="5a6c280e9acf0"  name="热点"  curr="5a6c280e9acf0" data={data}  />
        <PageApp />
    );
   }
}
